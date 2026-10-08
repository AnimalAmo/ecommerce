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
    // Pill replacing the cart button in the grids, and the pointer back to the
    // listing from the cart (client request, 27/09/2026). It has to work for a
    // property, an event and a gift box alike: "Contact the property" would not.
    'book_with_partner' => 'See how to book',

    // Card that replaces the booking box when the partner takes no online
    // orders (client request, 29/09/2026).
    'contacts' => [
        'title' => 'Contact the property',
        'intro' => 'This property does not take online bookings: contact it directly for availability and prices.',
        'business_name' => 'Run by',
        // The partner's, not the listing's (public_address, one per partner): a
        // partner with two properties has only one, and «Where it is» would pass
        // it off as the location of the listing being viewed.
        'address' => "Partner's address",
        'opening_hours' => 'Opening hours',
        // Partner's public contacts (client answer, 26/09/2026, point 6): the
        // screen shows an icon, the screen reader reads the label.
        'phone' => 'Phone',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'website' => 'Website',
        // Box under the booking box of partners paid online: only the public
        // address and the opening hours, no direct contacts.
        'info_title' => 'Useful information',
    ],

    // «See all photos» gallery on the detail pages (29/09/2026: the button
    // opened nothing). The button text stays in each page's file
    // (events/holiday/smartbox.view_all_photos).
    'gallery' => [
        'label' => 'Photos of :title',
        'photo_alt' => ':title, photo :number of :total',
        'previous' => 'Previous photo',
        'next' => 'Next photo',
    ],

    // «Choose your room» section of the structure page (only with two or
    // more rooms) and the chosen room in the booking card.
    'rooms' => [
        'title' => 'Choose your room',
        'max_guests' => 'Max guests: :count',
        'max_animals' => 'Max animals: :count',
        'amenities' => 'Room amenities',
        'select' => 'Select',
        'selected' => 'Selected',
        'booking_room' => 'Room: :name',
    ],
];
