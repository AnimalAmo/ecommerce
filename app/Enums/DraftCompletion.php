<?php

namespace App\Enums;

/**
 * Esito della chiusura di una bozza (spec §5.2). Il terzo caso, la bozza non
 * pubblicabile, non è un esito ma un errore: DraftNotPublishableException.
 */
enum DraftCompletion: string
{
    /** A catalogo (creata o aggiornata la riga della famiglia). */
    case Published = 'published';

    /** Pronta ma ferma: il partner online non è ancora pagabile su Stripe. */
    case AwaitingPayout = 'awaiting_payout';
}
