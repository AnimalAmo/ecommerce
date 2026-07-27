<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
     | Mailgun è il provider SMTP scelto dalla cliente (lug 2026). Serve solo al
     | mailer "mailgun" (transport API HTTP); il mailer "smtp" legge le MAIL_*.
     | ATTENZIONE alla regione: un dominio creato nell'area EU NON risponde sugli
     | endpoint US. Default EU, coerente col GDPR e con l'utenza italiana.
     */
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.eu.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Maps Embed API — chiave fornita dalla cliente (lug 2026); mappa "Dove siamo" nei detail catalogo.
    'google' => [
        'maps_key' => env('GOOGLE_MAPS_KEY'),
    ],

];
