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

    /**
     * Utente partner senza riga `partner_profiles`. Prima l'account Connect
     * nasceva su Stripe e a esplodere era l'update della riga inesistente: un
     * account orfano a ogni click, e un account connesso non si cancella da
     * codice. Si rifiuta prima di chiamare Stripe.
     */
    public static function profileMissing(): self
    {
        return new self(__('partner.errors.partner_profile_missing'));
    }

    /**
     * Smartbox di un partner che incassa fuori dalla piattaforma (richiesta
     * della cliente, 27/09/2026). Messaggio a sé: a chi si fa pagare
     * direttamente «completa il collegamento Stripe» suona come un onboarding
     * da finire, mentre la cosa da fare è passare all'incasso online.
     */
    public static function smartboxRequiresOnlinePayment(): self
    {
        return new self(__('partner.errors.smartbox_requires_online_payment'));
    }
}
