<?php

return [
    // 'MON, 30 MAY AT 15:30' — the mock alternates 'AT THE TIME OF': canonical 'AT'
    'event_time' => ':date AT :time',
    // Time line for the current day ('Today at 12:30' / 'TODAY AT 13:30')
    'today' => 'Today',
    // 'Mon, 8 Jan at 19:30' (home card) / 'Monday 8 January at 19:30' (detail header)
    'event_time_sentence' => ':date at :time',
    // General info of the event detail (verbatim XD pattern: 'from' today, 'from' with a date)
    'event_time_range_today' => 'Today from :start to :end',
    'event_time_range' => ':date from :start to :end',
    // Unit and partial total prices
    'per_person' => ':price per person',
    'per_night' => ':price per night',
    'per_hour' => ':price per hour',
    'for_nights' => ':price for :count nights',
    'for_hours' => ':price for :count hours',
    'for_people' => ':price for :count people',
    'from_price' => 'From :price',
    'free' => 'Free',
    'stars' => ':rating stars',
    // Events grid: activity duration line (uppercase via CSS)
    'duration_days' => 'Duration of :days days',
    // Activity detail: the XD weekend uses the text form, the other durations the numeric one
    'duration_label_weekend' => 'Duration of 3 days, two nights',
    'duration_label' => 'Duration of :days days, :nights nights',
    // "Valid for" of the smartbox detail
    'validity_years' => '{1} 1 year|[2,*] :years years',
    'validity_months' => '{1} 1 month|[2,*] :months months',
    // Smartbox favorites card: duration line = validity of the box (uppercase in the presenter)
    'valid_for' => 'Valid for :validity',
    // Cart labels: guests ('1 adult' / '3 adults' / 'N guests') and animals per species
    'guests_adults' => '{0} :count adults|{1} 1 adult|[2,*] :count adults',
    'guests_total' => '{0} :count guests|{1} 1 guest|[2,*] :count guests',
    'animals_cane' => '{0} :count dogs|{1} 1 dog|[2,*] :count dogs',
    'animals_gatto' => '{0} :count cats|{1} 1 cat|[2,*] :count cats',
    'animals_coniglio' => '{0} :count rabbits|{1} 1 rabbit|[2,*] :count rabbits',
];
