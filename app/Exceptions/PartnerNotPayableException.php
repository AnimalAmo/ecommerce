<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Il partner non ha completato l'onboarding Stripe: il suo servizio non può
 * andare a catalogo perché non esiste un account connesso su cui far nascere
 * l'incasso. Decisione della cliente (niente pubblicazione senza verifiche
 * bancarie complete) e, con i direct charges, anche vincolo tecnico.
 */
class PartnerNotPayableException extends RuntimeException
{
    public static function onboardingIncomplete(): self
    {
        return new self(__('partner.errors.stripe_onboarding_required'));
    }
}
