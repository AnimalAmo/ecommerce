<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Header, footer and shared partials
    |--------------------------------------------------------------------------
    */

    // Navigation menu (header)
    'menu' => [
        'holiday' => 'Holiday',
        'events' => 'Activities & Events',
        'smartbox' => 'Smartbox',
        'news' => 'Animal Times',
        'community' => 'Animal Network',
        'about' => 'About us',
        'become_partner' => 'Become a Partner',
    ],

    // Language/currency switcher + user actions (header)
    'language' => 'Language',
    'currency' => 'Currency',
    'login_register' => 'Log in / Sign up',
    'menu_open' => 'Open menu',
    'menu_close' => 'Close menu',
    'close' => 'Close',
    'favorites' => 'Favorites',
    'profile' => 'Profile',
    'my_profile' => 'My profile',
    'my_orders' => 'My orders',
    'logout' => 'Log out',

    // Mobile tabbar (app XD)
    'tabbar' => [
        'explore' => 'Explore',
        'community' => 'Community',
        'cart' => 'Cart',
    ],

    // Full footer
    'footer' => [
        'experiences' => 'Experiences',
        'services' => 'Services',
        'company' => 'Company',
        'help_support' => 'Help & Support',
        'events' => 'Activities & Events',
        'news' => 'Animal Times',
        'community' => 'Animal Network',
        'about' => 'About us',
        'work_with_us' => 'Work with us',
        'how_it_works' => 'How it works',
        'contact_us' => 'Contact us',
        'support' => 'Support',
        'copyright' => 'Copyright ©',
        'terms' => 'Terms and conditions',
        'privacy' => 'Privacy information',
        'cookie_policy' => 'Cookie policy',
        'manage_cookies' => 'Manage cookies',
    ],

    // Minimal footer (secondary pages)
    'footer_minimal' => [
        'privacy_policy' => 'Privacy policy',
        'cookie_policy' => 'Cookie policy',
    ],

    // Shared favorites/cart card
    'card' => [
        'remove_from_cart' => 'Remove from cart',
        'add_to_cart' => 'Add to cart',
        'remove_from_favorites' => 'Remove from favorites',
        'add_to_favorites' => 'Add to favorites',
        'starting_from' => 'From :price',
    ],

    // Shared booking widgets (animal/guest steppers + calendar)
    'booking' => [
        'decrease' => 'Decrease :name',
        'increase' => 'Increase :name',
        'previous_month' => 'Previous month',
        'next_month' => 'Next month',
        'species' => [
            'cane' => 'Dogs',
            'gatto' => 'Cats',
            'coniglio' => 'Rabbits',
        ],
        'dow' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'guests' => [
            'adults' => 'Adult',
            'teens' => 'Teens',
            'children' => 'Children',
            'adults_hint' => 'Age 17 - 99',
            'teens_hint' => 'Age 8 - 16',
            'children_hint' => 'Up to 7 years',
        ],
    ],

];
