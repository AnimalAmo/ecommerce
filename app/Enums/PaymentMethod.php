<?php

namespace App\Enums;

/**
 * Metodi di pagamento del checkout (righe del design step 2). Tutti passano
 * da Stripe: la carta col Payment Element, Apple/Google Pay con l'Express
 * Checkout Element.
 */
enum PaymentMethod: string
{
    case Card = 'card';
    case ApplePay = 'apple_pay';
    case GooglePay = 'google_pay';

    public function label(): string
    {
        return __('payment.methods.'.$this->value);
    }

    /** Codice del gateway (PaymentGateway.code) che processa il metodo. */
    public function gatewayCode(): string
    {
        return 'stripe';
    }

    /**
     * payment_method_types per il PaymentIntent Stripe (Apple/Google Pay
     * viaggiano come 'card' via Express Checkout Element).
     *
     * @return list<string>
     */
    public function stripePaymentMethodTypes(): array
    {
        return ['card'];
    }

    /** Wallet montati con l'Express Checkout Element (non il Payment Element). */
    public function usesExpressCheckout(): bool
    {
        return $this !== self::Card;
    }
}
