<?php

namespace App\Enums;

/**
 * Esito della chiusura di una bozza (spec §5.2). Il terzo caso, la bozza non
 * pubblicabile, non è un esito ma un errore: DraftNotPublishableException.
 *
 * L'esito dice che cosa è successo alla BOZZA, non perché. Difetto F2
 * dell'audit dei flussi (28/09/2026): `AwaitingPayout` era descritto come
 * «il partner online non è ancora pagabile su Stripe», e il wizard ne
 * ricavava quel messaggio anche per la smartbox di chi incassa in struttura.
 * Le due cause lasciano la bozza nello stesso stato (segnale, step finale,
 * status invariato) e i chiamanti che non parlano al partner
 * (AwaitingDraftPublisher, AdminServiceCreator) le trattano allo stesso modo:
 * un terzo caso cambierebbe il loro contratto senza cambiare cosa fanno. Chi
 * deve spiegare il blocco legge la causa dal profilo:
 * PartnerProfile::needsOnlinePaymentFor(), la stessa regola che DraftPublisher
 * usa per scegliere l'eccezione (il pannello admin ne ha una copia in
 * CreatesPartnerService::smartboxPaymentBlock).
 */
enum DraftCompletion: string
{
    /** A catalogo (creata o aggiornata la riga della famiglia). */
    case Published = 'published';

    /**
     * Pronta ma ferma, col segnale `publish_requested_at`: il partner non può
     * pubblicare questa famiglia. Per Stripe (onboarding a metà, conto non
     * pagabile, profilo assente) o, per una smartbox, perché incassa in
     * struttura e le serve l'incasso online.
     */
    case AwaitingPayout = 'awaiting_payout';
}
