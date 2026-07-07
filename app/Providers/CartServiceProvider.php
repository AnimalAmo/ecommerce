<?php

namespace App\Providers;

use App\Services\Cart\CartManager;
use App\Services\Cart\CartStorageInterface;
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    /**
     * Il manager è singleton (i due storage e i servizi di pricing/availability
     * si auto-wirano); l'interface risolve al manager per chi preferisce
     * type-hintare il contratto.
     */
    public function register(): void
    {
        $this->app->singleton(CartManager::class);

        $this->app->bind(CartStorageInterface::class, CartManager::class);
    }
}
