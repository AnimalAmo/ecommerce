<?php

namespace App\Enums;

/**
 * Metodi di pagamento del checkout (righe del design step 2). Tutti passano da
 * Stripe tranne PayPal (SDK classico): gatewayCode() decide il gateway,
 * stripePaymentMethodTypes() i payment_method_types del PaymentIntent.
 */
enum PaymentMethod: string
{
    case Card = 'card';
    case ApplePay = 'apple_pay';
    case GooglePay = 'google_pay';
    case Klarna = 'klarna';
    case Paypal = 'paypal';

    public function label(): string
    {
        return __('payment.methods.'.$this->value);
    }

    /** Codice del gateway (PaymentGateway.code) che processa il metodo. */
    public function gatewayCode(): string
    {
        return match ($this) {
            self::Paypal => 'paypal',
            default => 'stripe',
        };
    }

    /**
     * payment_method_types per il PaymentIntent Stripe (Apple/Google Pay
     * viaggiano come 'card' via Express Checkout Element). Null = non Stripe.
     *
     * @return list<string>|null
     */
    public function stripePaymentMethodTypes(): ?array
    {
        return match ($this) {
            self::Card, self::ApplePay, self::GooglePay => ['card'],
            self::Klarna => ['klarna'],
            self::Paypal => null,
        };
    }

    /** Wallet montati con l'Express Checkout Element (non il Payment Element). */
    public function usesExpressCheckout(): bool
    {
        return match ($this) {
            self::ApplePay, self::GooglePay => true,
            default => false,
        };
    }
}
