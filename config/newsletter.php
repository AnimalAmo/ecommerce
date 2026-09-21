<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailer e mittente
    |--------------------------------------------------------------------------
    | La newsletter dovrebbe partire da un sottodominio suo (news.animalamo.it):
    | Mailgun tiene una sola lista di soppressione per dominio, e chi segnala
    | come spam un numero della newsletter smetterebbe di ricevere anche le
    | conferme d'ordine (docs/newsletter-mailgun-istruzioni-cliente.md §A2.2).
    |
    | `mailer` vuoto = il mailer di default, cioè lo stesso dominio della posta
    | di servizio. Con il sottodominio pronto: NEWSLETTER_MAILER=mailgun-newsletter
    | e le due MAILGUN_NEWSLETTER_* (mailer dichiarato in config/mail.php).
    */

    'mailer' => env('NEWSLETTER_MAILER') ?: null,

    'from' => [
        'address' => env('NEWSLETTER_FROM_ADDRESS') ?: null,
        'name' => env('NEWSLETTER_FROM_NAME') ?: null,
    ],

    // Una casella letta da qualcuno: chi risponde a una newsletter va letto.
    'reply_to' => env('NEWSLETTER_REPLY_TO') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Double opt-in
    |--------------------------------------------------------------------------
    | Giorni di validità del link di conferma, dall'ultima mail spedita.
    */

    'confirmation_ttl_days' => (int) env('NEWSLETTER_CONFIRMATION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Invio a scaglioni
    |--------------------------------------------------------------------------
    | `rates`: i ritmi proposti nell'editor (invii all'ora, 0 = tutti subito).
    | `max_per_hour`: tetto che vale per ogni campagna, qualunque ritmo sia
    | stato scelto (limiti del piano Mailgun, riscaldamento di un dominio
    | nuovo). 0 = nessun tetto.
    | `batch_every_minutes`: un lotto ogni tot minuti; il lotto è grande quanto
    | basta a stare sotto il ritmo orario.
    */

    'rates' => [200, 500, 0],

    'max_per_hour' => (int) env('NEWSLETTER_MAX_PER_HOUR', 0),

    'batch_every_minutes' => (int) env('NEWSLETTER_BATCH_EVERY_MINUTES', 5),

];
