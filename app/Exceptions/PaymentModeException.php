<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Cambio di modalità di pagamento non ammesso: tornare online senza un conto
 * Stripe operativo renderebbe invendibili le schede del partner (il checkout
 * non ha un conto su cui far nascere l'incasso).
 */
class PaymentModeException extends RuntimeException
{
    public static function stripeRequired(): self
    {
        return new self(__('partner.payment_mode.errors.stripe_required'));
    }
}
