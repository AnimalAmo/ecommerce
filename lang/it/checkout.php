<?php

return [
    // Step 2: gateway non configurato/abilitato — box informativo al posto dell'element
    'payment_unavailable' => 'Il pagamento online non è al momento disponibile. Riprova tra qualche minuto o scegli un altro metodo.',
    // JS: conferma Stripe terminata senza esito positivo
    'payment_incomplete' => 'Il pagamento non è stato completato. Riprova.',

    // Ramo "paga in struttura" (partner senza pagamento online): lo step 2 è una conferma, niente Stripe
    'on_site' => [
        'step_label' => 'Conferma',
        'login_required' => 'Per prenotare con pagamento diretto al partner devi accedere al tuo account.',
        'mode_changed' => 'Il partner ora accetta il pagamento online: completa la prenotazione pagando qui.',
        'throttle' => 'Troppi tentativi di conferma. Riprova tra :seconds secondi.',
        'title' => 'Conferma la tua prenotazione',
        'notice' => 'Pagherai direttamente a :partner l\'importo di :amount, in struttura o sul suo sito.',
        // Stesso riquadro quando il partner non ha indicato la ragione sociale
        'notice_without_partner' => 'Pagherai direttamente al partner l\'importo di :amount, in struttura o sul suo sito.',
        'pay_on_website' => 'Vai al sito del partner per pagare o prenotare',
        'confirm_cta' => 'Conferma prenotazione',
        'failed' => 'Non siamo riusciti a registrare la prenotazione. Riprova tra qualche istante.',
        'already_placed' => 'Questa prenotazione risulta già registrata: non serve confermarla di nuovo.',
        'gift_not_allowed' => 'Il partner ora si fa pagare direttamente e non online: questo regalo non si può più acquistare. Rimuovilo dal carrello.',
        'thank_you' => 'Prenotazione confermata!',
        'thank_you_sub' => 'Ecco il riepilogo, controlla l\'email: il pagamento lo farai direttamente al partner.',
    ],

    // Stringhe di UI del funnel Checkout (blade commerce/checkout)
    'ui' => [
        'page_title' => 'Checkout — AnimalAmo',
        'step_data' => 'I tuoi dati',
        'step_payment' => 'Pagamento',
        'step_done' => 'Fatto!',
        'verify_personal_data' => 'Verifica i tuoi dati personali',
        'field_first_name' => 'Nome *',
        'field_last_name' => 'Cognome *',
        'field_email' => 'Email *',
        'field_country' => 'Paese',
        'field_phone' => 'Cellulare *',
        'field_recipient_email' => 'Email del destinatario *',
        'contact_note' => 'Ti contatteremo solo in caso di aggiornamenti importanti o modifiche alla tua prenotazione',
        'continue_purchase' => 'Prosegui l’acquisto',
        'select_payment_method' => 'Seleziona un metodo di pagamento',
        'saved_card' => 'Carta salvata •••• :last4',
        'new_card' => 'Usa un\'altra carta',
        'pay_now' => 'Paga ora',
        'order_summary' => 'Riepilogo dell’ordine',
        'dedicated_to' => 'Dedicato a: :name',
        'message' => 'Messaggio: :message',
        'total' => 'Totale',
        'taxes_included' => 'Tasse e commissioni comprese',
        'thank_you' => 'Grazie del tuo acquisto!',
        'gift_sent' => 'La Smartbox è stata mandata all’email: :email',
        'gift_sent_summary' => 'Ecco il riepilogo del tuo acquisto:',
        'check_email' => 'Ecco il riepilogo, controlla l’email',
        'back_home' => 'Torna alla Home',
        'go_to_purchases' => 'Vai ai tuoi acquisti',
    ],
];
