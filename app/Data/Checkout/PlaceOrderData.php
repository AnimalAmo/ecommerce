<?php

namespace App\Data\Checkout;

use App\Data\Cart\CartItemData;
use App\Enums\OrderPaymentMode;
use App\Enums\PaymentMethod;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Confine Livewire → PlaceOrderAction (immutabile): snapshot buyer, flusso
 * (normale/regalo), modalità di pagamento e righe carrello. totalCents arriva
 * dal CartManager, MAI dal client — il guard dell'action lo confronta con la
 * somma delle righe.
 *
 * Online porta metodo ed esito capture già verificato server-side; in
 * struttura non si muove denaro, quindi al loro posto c'è il token del
 * checkout che rende idempotente la conferma.
 */
final class PlaceOrderData extends Data
{
    /**
     * @param  Collection<int, CartItemData>  $items
     */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $country,
        public readonly bool $gift,
        /** Null solo per le prenotazioni in struttura (vedi onSite()). */
        public readonly ?PaymentMethod $paymentMethod,
        /** Null solo per le prenotazioni in struttura (vedi onSite()). */
        public readonly ?CheckoutCaptureResult $capture,
        public readonly Collection $items,
        public readonly int $totalCents,
        public readonly OrderPaymentMode $paymentMode = OrderPaymentMode::Online,
        public readonly ?string $checkoutToken = null,
        /** Copia del link del partner al momento dell'ordine: il profilo può cambiare dopo. */
        public readonly ?string $partnerPaymentUrl = null,
    ) {}

    /**
     * Prenotazione pagata direttamente al partner. Mai regalo: un buono
     * "da pagare in struttura" non ha nessuno che lo incassi per il destinatario.
     *
     * @param  Collection<int, CartItemData>  $items
     */
    public static function onSite(
        string $firstName,
        string $lastName,
        string $email,
        ?string $phone,
        string $country,
        Collection $items,
        int $totalCents,
        string $checkoutToken,
        ?string $partnerPaymentUrl,
    ): self {
        return new self(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            phone: $phone,
            country: $country,
            gift: false,
            paymentMethod: null,
            capture: null,
            items: $items,
            totalCents: $totalCents,
            paymentMode: OrderPaymentMode::OnSite,
            checkoutToken: $checkoutToken,
            partnerPaymentUrl: $partnerPaymentUrl,
        );
    }
}
