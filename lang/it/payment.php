<?php

return [
    // Errori del flusso di pagamento (toast/messaggi al checkout)
    'errors' => [
        'already_placed' => 'Questo pagamento risulta già registrato: il tuo ordine è confermato e non è stato effettuato alcun nuovo addebito.',
        'amount_changed' => 'L\'importo addebitato non corrisponde più al totale dell\'ordine: l\'addebito è stato stornato. Ricontrolla il riepilogo e riprova.',
        'capture_failed' => 'Pagamento non riuscito: nessun addebito confermato. Riprova o scegli un altro metodo.',
        'config_missing' => 'Questo metodo di pagamento non è al momento disponibile. Scegline un altro.',
        'init_failed' => 'Non siamo riusciti ad avviare il pagamento. Riprova tra qualche istante.',
        'refunded_after_error' => 'Si è verificato un errore durante la registrazione dell\'ordine: l\'importo è stato stornato. Riprova.',
        'refunded_after_soldout' => 'Il posto selezionato è appena andato esaurito: l\'importo è stato stornato.',
        'total_updated' => 'Il totale dell\'ordine è cambiato: ricontrolla il riepilogo e riprova.',
        'wallet_unavailable' => 'Questo metodo di pagamento non è disponibile su questo dispositivo o browser.',
    ],

    // Label PaymentMethod (righe metodo del checkout, step 2)
    'methods' => [
        'apple_pay' => 'Apple Pay',
        'card' => 'Carta di credito o di debito',
        'google_pay' => 'Google Pay',
        'klarna' => 'Klarna',
        'paypal' => 'PayPal',
    ],
];
