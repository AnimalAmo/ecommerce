<?php

return [
    // Region cards and titles ('Hotels and services in Liguria')
    'region_title' => 'Hotels and services in :region',
    // Region grid card badges (verbatim XD: 'Hotel' for structures, 'Services' for services)
    'results_title' => 'Here are the results:',
    'badge_hotel' => 'Hotel',
    'badge_services' => 'Services',

    // Empty grid state (XD app "Nessun risultato")
    'no_results_title' => 'No results found',
    'no_results_hint' => 'Try changing the filters to find other results.',
    'similar_results_title' => 'Results similar to your search:',

    // Mobile "Filters 2" modal (XD app "Filtri 2 ricerca")
    'filters_title' => 'Filters',
    'filter_price' => 'Price range',
    'filter_type' => 'Type',
    'filter_price_min' => 'Minimum',
    'filter_price_max' => 'Maximum',
    'filter_types' => [
        'hotel' => 'Hotel or property',
        'servizi' => 'Services',
        'attivita' => 'Activities',
        'eventi' => 'Events',
        'smartbox' => 'Smartbox',
    ],
    'smartbox_type_title' => 'Smartbox type',
    'smartbox_types' => [
        'soggiorno' => 'Stay Smartbox',
        'benessere' => 'Wellness Smartbox',
        'avventura' => 'Adventure Smartbox',
    ],
    // Compact Smartbox type chips in the results (XD app "Cerca - risultati - click 'filtri' – 2")
    'smartbox_chips' => [
        'soggiorno' => 'Stay',
        'benessere' => 'Wellness',
        'avventura' => 'Adventure',
    ],
    'buy' => 'Buy',
    'people_title' => 'Number of people',
    'people_groups' => [
        'coppia' => 'Couple',
        'famiglia' => 'Family',
        'gruppo' => 'Group (5+ people)',
    ],
    'show_results' => 'Show :count results',
    // At zero the button cannot promise results: it says what it actually does.
    'close_filters' => 'Close filters',
    // Detail pages of a partner without online payment: the total is paid to them.
    'pay_on_site' => 'Pay the partner directly, on site or on their website',

    // Card that replaces the booking box when the partner takes no online
    // orders (client request, 29/09/2026).
    'contacts' => [
        'title' => 'Contact the property',
        'intro' => 'This property does not take online bookings: contact it directly for availability and prices.',
        'business_name' => 'Run by',
        'address' => 'Where it is',
    ],
];
