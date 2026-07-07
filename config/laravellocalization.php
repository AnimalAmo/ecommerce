<?php

return [

    'supportedLocales' => [
        'it' => ['name' => 'Italian', 'script' => 'Latn', 'native' => 'italiano', 'regional' => 'it_IT'],
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
    ],

    'useAcceptLanguageHeader' => false,

    'hideDefaultLocaleInURL' => true,

    'localesOrder' => [],

    'localesMapping' => [],

    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),

    'urlsIgnored' => ['/webhooks', '/webhooks/*', '/locale', '/locale/*'],

    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
