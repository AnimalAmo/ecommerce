<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Credenziali del gateway mancanti in config/payment.php. Lanciata alla
 * risoluzione del gateway (StripeClient bind / costruttore PaypalGateway)
 * e catturata a monte: toast payment.errors.config_missing al checkout,
 * 400 nei controller webhook. Mai un fatal per l'utente.
 */
class PaymentConfigurationException extends RuntimeException
{
    public static function missing(string $gatewayCode): self
    {
        return new self("Payment gateway [{$gatewayCode}] is not configured: missing credentials.");
    }
}
