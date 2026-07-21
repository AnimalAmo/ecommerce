<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe — carta, Apple Pay, Google Pay
    |--------------------------------------------------------------------------
    | 'key' è la publishable key passata al JS dal componente checkout
    | (mai via VITE_*), 'secret' inizializza lo StripeClient iniettato,
    | 'webhook_secret' verifica la firma degli eventi in ingresso.
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
