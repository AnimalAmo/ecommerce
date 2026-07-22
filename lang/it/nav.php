<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Header, footer e partial condivisi
    |--------------------------------------------------------------------------
    */

    // Menu di navigazione (header)
    'menu' => [
        'holiday' => 'Holiday',
        'events' => 'Attività ed Eventi',
        'smartbox' => 'Smartbox',
        'news' => 'Animal Times',
        'community' => 'Animal Network',
        'about' => 'Chi siamo',
        'become_partner' => 'Diventa Partner',
    ],

    // Switcher lingua/valuta + azioni utente (header)
    'language' => 'Lingua',
    'login_register' => 'Accedi / Registrati',
    'menu_open' => 'Apri menu',
    'menu_close' => 'Chiudi menu',
    'close' => 'Chiudi',
    'favorites' => 'Preferiti',
    'profile' => 'Profilo',
    'my_profile' => 'Il mio profilo',
    'my_orders' => 'I miei ordini',
    'logout' => 'Esci',

    // Tabbar mobile (XD app)
    'tabbar' => [
        'explore' => 'Esplora',
        'community' => 'Community',
        'cart' => 'Carrello',
    ],

    // Footer completo
    'footer' => [
        'experiences' => 'Esperienze',
        'services' => 'Servizi',
        'company' => 'Azienda',
        'help_support' => 'Help & Support',
        'events' => 'Attività ed Eventi',
        'news' => 'Animal Times',
        'community' => 'Animal Network',
        'about' => 'Chi siamo',
        'work_with_us' => 'Lavora con noi',
        'how_it_works' => 'Come funziona',
        'contact_us' => 'Contattaci',
        'copyright' => 'Copyright ©',
        'terms' => 'Termini e condizioni',
        'privacy' => 'Informazioni privacy',
        'cookie_policy' => 'Informativa cookie',
        'manage_cookies' => 'Gestisci cookie',
    ],

    // Footer minimal (pagine secondarie)
    'footer_minimal' => [
        'privacy_policy' => 'Privacy policy',
        'cookie_policy' => 'Cookie policy',
    ],

    // Card preferiti/carrello condivisa
    'card' => [
        'remove_from_cart' => 'Rimuovi dal carrello',
        'add_to_cart' => 'Aggiungi al carrello',
        'remove_from_favorites' => 'Rimuovi dai preferiti',
        'add_to_favorites' => 'Aggiungi ai preferiti',
        'starting_from' => 'A partire da :price',
    ],

    // Widget prenotazione condivisi (stepper animali/ospiti + calendario)
    'booking' => [
        'decrease' => 'Diminuisci :name',
        'increase' => 'Aumenta :name',
        'previous_month' => 'Mese precedente',
        'next_month' => 'Mese successivo',
        'species' => [
            'cane' => 'Cani',
            'gatto' => 'Gatti',
            'coniglio' => 'Conigli',
        ],
        'dow' => ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
        'guests' => [
            'adults' => 'Adulto',
            'teens' => 'Ragazzi',
            'children' => 'Bambini',
            'adults_hint' => 'Età 17 - 99',
            'teens_hint' => 'Età 8 - 16',
            'children_hint' => 'Fino a 7 anni',
        ],
    ],

];
