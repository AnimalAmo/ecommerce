<?php

use App\Providers\AppServiceProvider;
use App\Providers\CartServiceProvider;
use App\Providers\PaymentServiceProvider;

return [
    AppServiceProvider::class,
    CartServiceProvider::class,
    PaymentServiceProvider::class,
];
