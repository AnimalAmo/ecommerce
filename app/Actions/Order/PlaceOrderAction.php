<?php

namespace App\Actions\Order;

use App\Data\Cart\CartItemData;
use App\Data\Checkout\CheckoutCaptureResult;
use App\Data\Checkout\OrderPipelineData;
use App\Data\Checkout\PlaceOrderData;
use App\Exceptions\CartValidationException;
use App\Exceptions\OrderAlreadyPlacedException;
use App\Models\Order\Order;
use App\Models\OrderPayment\OrderPayment;
use App\Pipes\Order\ClearCartPipe;
use App\Pipes\Order\CreateOrderItemsPipe;
use App\Pipes\Order\CreateOrderPaymentPipe;
use App\Pipes\Order\CreateOrderPipe;
use App\Pipes\Order\ReserveAvailabilityPipe;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use RuntimeException;

/**
 * Creazione ordine capture-first (port matsuri): pipeline in DB::transaction,
 * tutto-o-niente — se una riga è sold-out la reserve lancia e nulla resta a db
 * (il chiamante storna l'incasso col transaction id del capture result).
 */
class PlaceOrderAction
{
    /**
     * @throws OrderAlreadyPlacedException il capture ha GIÀ un OrderPayment: esito idempotente, NIENTE refund
     * @throws CartValidationException riga non più disponibile (rollback totale)
     * @throws RuntimeException totale righe ≠ importo incassato (mai ordini disallineati)
     */
    public function execute(PlaceOrderData $data): Order
    {
        $this->guardTotalMatchesCapture($data);

        try {
            $carrier = DB::transaction(function () use ($data): OrderPipelineData {
                // Idempotenza: lo stesso incasso (provider + gateway session id)
                // non genera mai un secondo ordine — replay del callback via
                // devtools, race del carrello guest condiviso fra sessioni.
                if (($existing = $this->findRegisteredOrder($data->capture)) !== null) {
                    throw OrderAlreadyPlacedException::forOrder($existing);
                }

                return Pipeline::send(new OrderPipelineData($data))
                    ->through([
                        ReserveAvailabilityPipe::class,
                        CreateOrderPipe::class,
                        CreateOrderItemsPipe::class,
                        CreateOrderPaymentPipe::class,
                        ClearCartPipe::class,
                    ])
                    ->thenReturn();
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Backstop della race fra transaction concorrenti sullo stesso
            // capture: il vincolo unico (provider, gateway_session_id) fa
            // fallire la seconda insert — anche qui esito idempotente.
            if (($existing = $this->findRegisteredOrder($data->capture)) !== null) {
                throw OrderAlreadyPlacedException::forOrder($existing);
            }

            throw $exception;
        }

        return $carrier->order;
    }

    /** Ordine già registrato per questo incasso (provider + gateway session id), se esiste. */
    public function findRegisteredOrder(CheckoutCaptureResult $capture): ?Order
    {
        if ($capture->gatewaySessionId === null || $capture->provider === null) {
            return null;
        }

        return OrderPayment::query()
            ->where('provider', $capture->provider)
            ->where('gateway_session_id', $capture->gatewaySessionId)
            ->first()
            ?->order;
    }

    /**
     * Guard totale-vs-capture: l'importo verificato dal gateway (expected =
     * totalCents del CartManager) deve coincidere con la somma delle righe —
     * mai un ordine con totale diverso dall'incasso.
     */
    private function guardTotalMatchesCapture(PlaceOrderData $data): void
    {
        $itemsTotal = (int) $data->items->sum(fn (CartItemData $item): int => $item->priceCents);

        if ($data->capture->succeeded && $itemsTotal !== $data->totalCents) {
            throw new RuntimeException(sprintf(
                'Order total mismatch: cart lines sum to %d cents but %d cents were captured.',
                $itemsTotal,
                $data->totalCents,
            ));
        }
    }
}
