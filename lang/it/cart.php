<?php

return [
    // Messaggi di CartValidationException (toast danger nei componenti)
    'unavailable_dates' => 'Le date selezionate non sono disponibili.',
    'unavailable_day' => 'Il giorno selezionato non è disponibile.',
    'past_date' => 'Seleziona una data futura.',
    'invalid_range' => 'La data di check-out deve essere successiva al check-in.',
    'invalid_times' => 'L\'orario di fine deve essere successivo all\'inizio.',
    'sold_out' => 'Non ci sono abbastanza posti disponibili.',
    'not_purchasable' => 'Questo prodotto non è acquistabile.',
    'single_partner' => 'Nel carrello puoi avere prodotti di una sola struttura per volta. Completa questo acquisto, oppure svuota il carrello per ricominciare.',
    'product_without_owner' => 'Questo prodotto non è al momento acquistabile. Riprova più tardi.',
    'invalid_participants' => 'Il numero di partecipanti selezionato non è valido.',
    // Toast di conferma aggiunta
    'added' => 'Aggiunto al carrello.',
    // Riga validità della card smartbox ('Smartbox valida per 12 mesi', :validity via Format::validity)
    'gift_validity' => 'Smartbox valida per :validity',

    // Stringhe di UI della pagina Carrello (blade commerce/cart)
    'ui' => [
        'page_title' => 'Carrello — AnimalAmo',
        'title' => 'Carrello',
        'empty_heading' => 'Non hai ancora prenotato attività',
        'empty_text' => 'Prepara i tuoi amici a quattro zampe: organizza la vostra prossima avventura.',
        'empty_cta' => 'Esperienze pensate per te',
        // Seconda CTA: finché i partner non pubblicano, /eventi è a sua volta vuoto —
        // Animal Times ha articoli veri, quindi lo stato vuoto porta anche lì.
        'empty_cta_news' => 'Leggi le storie di Animal Times',
        'most_loved' => 'Le attività più amate su Animal-amo',
        'prev_cards' => 'Card precedenti',
        'next_cards' => 'Card successive',
        'page_number' => 'Pagina :number',
        'item_one' => 'articolo',
        'item_many' => 'articoli',
        'edit' => 'Modifica',
        'remove' => 'Elimina',
        'gift_dedication_placeholder' => 'Dedicato a',
        'gift_message_placeholder' => 'Messaggio',
        'total' => 'Totale',
        'taxes_included' => 'Tasse e commissioni comprese',
        'promo_code' => 'Inserisci codice promozionale',
        'secure_payment' => 'Metodo di pagamento sicuro',
        'free_cancellation' => 'Cancellazione gratuita',
        'free_cancellation_note' => '(Non oltre 2 settimane prima dell’evento)',
        'go_to_checkout' => 'Vai al checkout',
        // CTA della barra fissa mobile (XD app "Carrello - click 'procedi'")
        'proceed_checkout' => 'Procedi con il checkout',
        'edit_booking' => 'Modifica prenotazione',
        'check_in' => 'Check-in',
        'check_out' => 'Check-out',
        'day' => 'Giorno',
        'times' => 'Orari',
        'from' => 'Dalle',
        'to' => 'Alle',
        'guests' => 'Ospiti',
        'animals' => 'Animali',
        'cancel' => 'Annulla',
        'confirm' => 'Conferma',
    ],
];
