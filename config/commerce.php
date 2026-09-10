<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provvigione AnimalAmo
    |--------------------------------------------------------------------------
    | Decisione del commercialista della cliente del 2026-09-09: sotto i 50 €
    | nessuna provvigione, da 50 € in su il 10% sul totale della prenotazione.
    | In basis points perché i soldi qui sono sempre interi (1000 bp = 10%).
    | Il singolo partner può derogare via partner_profiles.
    */

    'commission' => [
        'rate_bp' => (int) env('COMMISSION_RATE_BP', 1000),
        'min_cents' => (int) env('COMMISSION_MIN_CENTS', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rilascio del pagamento al partner
    |--------------------------------------------------------------------------
    | Giorni minimi fra incasso e bonifico al partner. 14 = il recesso sui
    | cofanetti (decisione 7). Per le prenotazioni datate vale la scadenza
    | della cancellazione gratuita, quando è più avanti (PayoutReleaseSchedule).
    */

    'payout' => [
        'release_delay_days' => (int) env('PAYOUT_RELEASE_DELAY_DAYS', 14),
    ],

];
