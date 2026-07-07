<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe — carta, Apple Pay, Google Pay, Klarna
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

    /*
    |--------------------------------------------------------------------------
    | PayPal — SDK JS classico + Orders API v2
    |--------------------------------------------------------------------------
    | 'webhook_id' (dashboard PayPal) serve alla verifica firma dei webhook:
    | senza, gli eventi vengono accettati con warning nei log (come matsuri).
    */

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

];
