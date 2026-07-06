<?php

return [
    // 'LUN, 30 MAG ALLE 15:30' — il mock alterna 'ALLE ORE': canonico 'ALLE'
    'event_time' => ':date ALLE :time',
    // Riga orario del giorno corrente ('Oggi alle ore 12:30' / 'OGGI ALLE 13:30')
    'today' => 'Oggi',
    // 'Lun, 8 Gen alle ore 19:30' (card home) / 'Lunedì 8 Gennaio alle ore 19:30' (testata dettaglio)
    'event_time_sentence' => ':date alle ore :time',
    // Info generali del dettaglio evento (pattern XD verbatim: 'dalle' oggi, 'dalle ore' con data)
    'event_time_range_today' => 'Oggi dalle :start alle :end',
    'event_time_range' => ':date dalle ore :start alle :end',
    // Prezzi unitari e totali parziali
    'per_person' => ':price a persona',
    'per_night' => ':price a notte',
    'per_hour' => ':price all’ora',
    'for_nights' => ':price per :count notti',
    'for_hours' => ':price per :count ore',
    'for_people' => ':price per :count persone',
    'from_price' => 'A partire da :price',
    'free' => 'Gratis',
    'stars' => ':rating stelle',
    // Griglia eventi: riga durata attività (maiuscole via CSS)
    'duration_days' => 'Durata di :days giorni',
    // Dettaglio attività: il weekend XD ha la forma testuale, le altre durate quella numerica
    'duration_label_weekend' => 'Durata di 3 giorni, due notti',
    'duration_label' => 'Durata di :days giorni, :nights notti',
    // "Valido per" del dettaglio smartbox
    'validity_years' => '{1} 1 anno|[2,*] :years anni',
    'validity_months' => '{1} 1 mese|[2,*] :months mesi',
];
