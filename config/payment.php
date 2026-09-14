<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe — carta, Apple Pay, Google Pay
    |--------------------------------------------------------------------------
    | 'key' è la publishable key passata al JS dal componente checkout
    | (mai via VITE_*), 'secret' inizializza lo StripeClient iniettato,
    | 'webhook_secret' verifica la firma degli eventi in ingresso: accetta una
    | lista separata da virgole, perché Connect richiede due endpoint sullo
    | stesso URL (piattaforma e account connessi) e Stripe dà a ciascuno il
    | proprio whsec_.
    */

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
