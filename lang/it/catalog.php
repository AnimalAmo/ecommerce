<?php

return [
    // Card e titoli regione ('Hotel e servizi in Liguria')
    'region_title' => 'Hotel e servizi in :region',
    // Badge card griglia regione (verbatim XD: 'Hotel' per le strutture, 'Servizi' per i servizi)
    'results_title' => 'Ecco i risultati:',
    'badge_hotel' => 'Hotel',
    'badge_services' => 'Servizi',

    // Stato vuoto della griglia (XD app "Nessun risultato")
    'no_results_title' => 'Nessun risultato trovato',
    'no_results_hint' => 'Prova a modificare i filtri per trovare altri risultati.',
    'similar_results_title' => 'Risultati simili alla tua ricerca:',

    // Modal "Filtri 2" mobile (XD app "Filtri 2 ricerca")
    'filters_title' => 'Filtri',
    'filter_price' => 'Fascia di prezzo',
    'filter_type' => 'Tipologia',
    'filter_price_min' => 'Minimo',
    'filter_price_max' => 'Massimo',
    'filter_types' => [
        'hotel' => 'Hotel o struttura',
        'servizi' => 'Servizi',
        'attivita' => 'Attività',
        'eventi' => 'Eventi',
        'smartbox' => 'Smartbox',
    ],
    'smartbox_type_title' => 'Tipologia Smartbox',
    'smartbox_types' => [
        'soggiorno' => 'Soggiorno Smartbox',
        'benessere' => 'Benessere Smartbox',
        'avventura' => 'Avventura Smartbox',
    ],
    // Chip compatte delle tipologie Smartbox nei risultati (XD app "Cerca - risultati - click 'filtri' – 2")
    'smartbox_chips' => [
        'soggiorno' => 'Soggiorno',
        'benessere' => 'Benessere',
        'avventura' => 'Avventura',
    ],
    'buy' => 'Acquista',
    'people_title' => 'Numero di persone',
    'people_groups' => [
        'coppia' => 'Coppia',
        'famiglia' => 'Famiglia',
        'gruppo' => 'Gruppo (+5 persone)',
    ],
    'show_results' => 'Mostra :count risultati',
    // A zero il bottone non può promettere risultati: dice cosa fa davvero.
    'close_filters' => 'Chiudi i filtri',
    // Schede dettaglio di un partner senza pagamento online: il totale si salda a lui.
    'pay_on_site' => 'Pagamento direttamente al partner, in struttura o sul suo sito',
    // Pill al posto del pulsante carrello nelle griglie, e rimando alla scheda dal
    // carrello (richiesta della cliente, 27/09/2026). Deve funzionare per una
    // struttura, un evento e un cofanetto: «Contatta la struttura» non andrebbe bene.
    'book_with_partner' => 'Scopri come prenotare',

    // Card che prende il posto del box prenotazione quando il partner non
    // prende ordini online (richiesta della cliente, 29/09/2026).
    'contacts' => [
        'title' => 'Contatta la struttura',
        'intro' => 'Questa struttura non prende prenotazioni online: contattala direttamente per disponibilità e prezzi.',
        'business_name' => 'Gestita da',
        // Del partner e non della scheda (public_address, uno per partner): chi ha
        // due strutture ne ha uno solo, e «Dove si trova» lo darebbe per il luogo
        // della scheda che si sta guardando.
        'address' => 'Indirizzo del partner',
        'opening_hours' => 'Orari di apertura',
        // Recapiti pubblici del partner (risposta della cliente, 26/09/2026,
        // punto 6): a video c'è l'icona, l'etichetta la legge lo screen reader.
        'phone' => 'Telefono',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'website' => 'Sito web',
        // Riquadro sotto il box prenotazione di chi incassa online: solo
        // indirizzo pubblico e orari, i recapiti diretti no.
        'info_title' => 'Informazioni utili',
    ],

    // Galleria «Vedere tutte le foto» delle schede di dettaglio (segnalazione
    // del 29/09/2026: il pulsante non apriva niente). Il testo del pulsante
    // resta nel file di ogni scheda (events/holiday/smartbox.view_all_photos).
    'gallery' => [
        'label' => 'Foto di :title',
        'photo_alt' => ':title, foto :number di :total',
        'previous' => 'Foto precedente',
        'next' => 'Foto successiva',
    ],

    // Sezione «Scegli la camera» della scheda struttura (solo con almeno due
    // stanze) e stanza scelta nella booking card.
    'rooms' => [
        'title' => 'Scegli la camera',
        'max_guests' => 'Ospiti max: :count',
        'max_animals' => 'Animali max: :count',
        'amenities' => 'Servizi della camera',
        'select' => 'Seleziona',
        'selected' => 'Selezionata',
        'booking_room' => 'Camera: :name',
    ],
];
