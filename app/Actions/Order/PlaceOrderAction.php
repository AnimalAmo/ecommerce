<?php

namespace App\Actions\Order;

use App\Data\Cart\CartItemData;
use App\Data\Checkout\CheckoutCaptureResult;
use App\Data\Checkout\OrderPipelineData;
use App\Data\Checkout\PlaceOrderData;
use App\Enums\OrderPaymentMode;
use App\Events\OnSiteOrderConfirmed;
use App\Exceptions\CartValidationException;
use App\Exceptions\OrderAlreadyPlacedException;
use App\Models\Order\Order;
use App\Models\OrderPayment\OrderPayment;
use App\Pipes\Order\ClearCartPipe;
use App\Pipes\Order\ConfirmOnSiteOrderPipe;
use App\Pipes\Order\CreateOrderItemsPipe;
use App\Pipes\Order\CreateOrderPaymentPipe;
use App\Pipes\Order\CreateOrderPayoutsPipe;
use App\Pipes\Order\CreateOrderPipe;
use App\Pipes\Order\ReserveAvailabilityPipe;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use InvalidArgumentException;
use RuntimeException;

/**
 * Creazione ordine in DB::transaction, tutto-o-niente (port matsuri).
 *
 * Online è capture-first: se una riga è sold-out la reserve lancia, nulla
 * resta a db e il chiamante storna l'incasso col transaction id del capture.
 * In struttura non si muove denaro: stessa pipeline senza payout né
 * pagamento, idempotente sul token del checkout.
 */
class PlaceOrderAction
{
    /**
     * @throws OrderAlreadyPlacedException capture o token hanno GIÀ un ordine: esito idempotente, NIENTE refund
     * @throws CartValidationException riga non più disponibile (rollback totale)
     * @throws RuntimeException nessuna riga, o totale ≠ somma righe (mai ordini disallineati)
     * @throws InvalidArgumentException dati incompleti per la modalità scelta, o regalo in struttura
     */
    public function execute(PlaceOrderData $data): Order
    {
        return $data->paymentMode === OrderPaymentMode::OnSite
            ? $this->placeOnSite($data)
            : $this->placeOnline($data);
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

    /** Prenotazione in struttura già registrata con questo token di checkout, se esiste. */
    public function findOnSiteOrder(string $checkoutToken): ?Order
    {
        return Order::query()->where('checkout_token', $checkoutToken)->first();
    }

    private function placeOnline(PlaceOrderData $data): Order
    {
        if ($data->paymentMethod === null || $data->capture === null) {
            throw new InvalidArgumentException('An online order needs the payment method and the verified capture.');
        }

        $capture = $data->capture;

        try {
            $carrier = DB::transaction(function () use ($data, $capture): OrderPipelineData {
                // Idempotenza PRIMA delle guardie: il replay del callback arriva
                // col carrello già svuotato e deve restare "già registrato",
                // non diventare un errore che fa partire lo storno.
                if (($existing = $this->findRegisteredOrder($capture)) !== null) {
                    throw OrderAlreadyPlacedException::forOrder($existing);
                }

                $this->guardLines($data, checkTotal: $capture->succeeded);

                return Pipeline::send(new OrderPipelineData($data))
                    ->through([
                        ReserveAvailabilityPipe::class,
                        CreateOrderPipe::class,
                        CreateOrderItemsPipe::class,
                        CreateOrderPayoutsPipe::class,
                        CreateOrderPaymentPipe::class,
                        ClearCartPipe::class,
                    ])
                    ->thenReturn();
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Backstop della race fra transaction concorrenti sullo stesso
            // capture: il vincolo unico (provider, gateway_session_id) fa
            // fallire la seconda insert — anche qui esito idempotente.
            if (($existing = $this->findRegisteredOrder($capture)) !== null) {
                throw OrderAlreadyPlacedException::forOrder($existing);
            }

            throw $exception;
        }

        return $carrier->order;
    }

    private function placeOnSite(PlaceOrderData $data): Order
    {
        if ($data->checkoutToken === null || $data->checkoutToken === '') {
            throw new InvalidArgumentException('An on-site order needs the checkout token.');
        }

        // Un buono "da pagare in struttura" non ha nessuno che lo incassi per il
        // destinatario: la regola vale per ogni chiamante, non solo per il checkout.
        if ($data->gift || $data->items->contains(fn (CartItemData $item): bool => $item->isGift)) {
            throw new InvalidArgumentException('An on-site booking is never a gift.');
        }

        $token = $data->checkoutToken;

        try {
            $carrier = DB::transaction(function () use ($data, $token): OrderPipelineData {
                // Senza gateway non c'è un incasso che faccia da chiave: il token
                // del checkout ferma il doppio click e il replay dello stesso
                // snapshot. Una seconda tab ha un componente e un token suoi: la
                // ferma solo il carrello già svuotato dal primo ordine.
                if (($existing = $this->findOnSiteOrder($token)) !== null) {
                    throw OrderAlreadyPlacedException::forOrder($existing);
                }

                $this->guardLines($data, checkTotal: true);

                // Niente payout né pagamento: nessuna commissione e nessuna
                // riga che resterebbe Pending per sempre nel registro.
                return Pipeline::send(new OrderPipelineData($data))
                    ->through([
                        ReserveAvailabilityPipe::class,
                        CreateOrderPipe::class,
                        CreateOrderItemsPipe::class,
                        ConfirmOnSiteOrderPipe::class,
                        ClearCartPipe::class,
                    ])
                    ->thenReturn();
            });
        } catch (UniqueConstraintViolationException|CartValidationException $exception) {
            // Due conferme concorrenti con lo stesso token hanno superato
            // entrambe la ricerca: l'unique su checkout_token fa perdere la
            // seconda. Sugli ultimi posti di un evento la seconda si ferma
            // prima, sul lock della reserve, e poi vede i posti presi dalla
            // prima: anche quello è un "già registrato", non un sold-out.
            if (($existing = $this->findOnSiteOrder($token)) !== null) {
                throw OrderAlreadyPlacedException::forOrder($existing);
            }

            throw $exception;
        }

        // Dopo il commit: le mail partono solo per un ordine che esiste davvero.
        OnSiteOrderConfirmed::dispatch($carrier->order);

        return $carrier->order;
    }

    /**
     * Mai un ordine vuoto, mai un totale diverso dalla somma delle righe.
     * Online il totale si confronta solo a capture riuscito (è l'importo
     * verificato dal gateway); in struttura sempre, perché nessun gateway lo ha
     * controllato prima.
     */
    private function guardLines(PlaceOrderData $data, bool $checkTotal): void
    {
        if ($data->items->isEmpty()) {
            throw new RuntimeException('Order has no lines: nothing to book.');
        }

        if (! $checkTotal) {
            return;
        }

        $itemsTotal = (int) $data->items->sum(fn (CartItemData $item): int => $item->priceCents);

        if ($itemsTotal !== $data->totalCents) {
            throw new RuntimeException(sprintf(
                'Order total mismatch: cart lines sum to %d cents but the order total is %d cents.',
                $itemsTotal,
                $data->totalCents,
            ));
        }
    }
}
