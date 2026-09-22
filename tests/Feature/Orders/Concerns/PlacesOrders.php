<?php

namespace Tests\Feature\Orders\Concerns;

use App\Actions\Order\PlaceOrderAction;
use App\Data\Checkout\CheckoutCaptureResult;
use App\Data\Checkout\PlaceOrderData;
use App\Enums\PaymentMethod;
use App\Models\Order\Order;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Costruzione di un ordine attraverso la pipeline vera (carrello →
 * PlaceOrderAction), condivisa dai test che ne hanno bisogno: rifare a mano
 * l'ordine renderebbe i test verdi per costruzione e ciechi alle regressioni
 * della pipeline.
 */
trait PlacesOrders
{
    private ?User $seller = null;

    private ?User $offlineSeller = null;

    private function cart(): CartManager
    {
        return app(CartManager::class);
    }

    private function placeOrder(
        bool $gift = false,
        ?int $totalCentsOverride = null,
        string $gatewaySessionId = 'pi_test_1',
        ?Collection $itemsOverride = null,
        ?int $netCents = null,
    ): Order {
        $data = new PlaceOrderData(
            firstName: 'Giulia',
            lastName: 'Rossi',
            email: 'giulia.rossi@gmail.com',
            phone: '340 5738920',
            country: 'Italia',
            gift: $gift,
            paymentMethod: PaymentMethod::Card,
            capture: CheckoutCaptureResult::success(
                gatewaySessionId: $gatewaySessionId,
                transactionId: $gatewaySessionId,
                provider: 'stripe',
                providerResponse: ['status' => 'succeeded'],
                netCents: $netCents,
            ),
            items: $itemsOverride ?? $this->cart()->items($gift),
            totalCents: $totalCentsOverride ?? $this->cart()->total($gift),
        );

        return app(PlaceOrderAction::class)->execute($data);
    }

    /**
     * Prenotazione "paga in struttura" attraverso la pipeline vera: nessun
     * capture, l'idempotenza la dà il token del checkout.
     */
    private function placeOnSiteOrder(
        ?string $checkoutToken = null,
        ?int $totalCentsOverride = null,
        ?Collection $itemsOverride = null,
        ?string $partnerPaymentUrl = null,
    ): Order {
        $data = PlaceOrderData::onSite(
            firstName: 'Giulia',
            lastName: 'Rossi',
            email: 'giulia.rossi@gmail.com',
            phone: '340 5738920',
            country: 'Italia',
            items: $itemsOverride ?? $this->cart()->items(),
            totalCents: $totalCentsOverride ?? $this->cart()->total(),
            checkoutToken: $checkoutToken ?? (string) Str::ulid(),
            partnerPaymentUrl: $partnerPaymentUrl,
        );

        return app(PlaceOrderAction::class)->execute($data);
    }

    /** Venditore senza pagamento online: pubblica senza Stripe, il cliente lo paga fuori piattaforma. */
    private function offlineSeller(): User
    {
        return $this->offlineSeller ??= User::factory()->offlinePartner()->create();
    }

    /** Riga struttura: 01/08 → 06/08 (5 notti), 2 adulti e 1 cane. */
    private function addStructureLine(Structure $structure): int|string
    {
        return $this->cart()->addItem('structure', $structure->id, [
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-06',
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }

    /** Riga regalo: smartbox con metadati gift completi (destinatario incluso). */
    private function addGiftSmartboxLine(SmartboxPackage $box): int|string
    {
        return $this->cart()->addItem('smartbox_package', $box->id, [
            'animals' => ['cane' => 1],
            'gift' => [
                'dedication' => 'Marco',
                'message' => 'Tanti auguri!',
                'recipient_email' => 'marco@example.com',
            ],
        ], true)->key;
    }

    /**
     * Il venditore di tutto ciò che finisce in questi carrelli: un ordine ha un
     * solo partner, quindi due prodotti di proprietari diversi non possono
     * coesistere in un carrello (CartManager::guardSinglePartner).
     */
    private function seller(): User
    {
        // Collegato a Stripe: con i direct charges un venditore senza
        // account connesso non può incassare, quindi non può nemmeno vendere.
        return $this->seller ??= User::factory()->stripeConnected()->create();
    }
}
