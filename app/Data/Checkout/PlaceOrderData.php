<?php

namespace App\Data\Checkout;

use App\Data\Cart\CartItemData;
use App\Enums\PaymentMethod;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Confine Livewire → PlaceOrderAction (immutabile): snapshot buyer, flusso
 * (normale/regalo), metodo di pagamento, esito capture già verificato
 * server-side e righe carrello. totalCents arriva dal CartManager, MAI dal
 * client — il guard dell'action lo confronta con la somma delle righe.
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
        public readonly PaymentMethod $paymentMethod,
        public readonly CheckoutCaptureResult $capture,
        public readonly Collection $items,
        public readonly int $totalCents,
    ) {}
}
