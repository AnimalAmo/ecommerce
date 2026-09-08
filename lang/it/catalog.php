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
];
