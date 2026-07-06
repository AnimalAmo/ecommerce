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
    // Toast di conferma aggiunta
    'added' => 'Aggiunto al carrello.',
    // Riga validità della card smartbox ('Smartbox valida per 12 mesi', :validity via Format::validity)
    'gift_validity' => 'Smartbox valida per :validity',
];
