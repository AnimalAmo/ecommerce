<?php

namespace App\Providers;

use App\Services\Cart\CartManager;
use App\Services\Cart\CartStorageInterface;
use App\Services\Cart\DatabaseCartStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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

    /**
     * Un prodotto che esce dal catalogo esce subito da tutti i carrelli a
     * database, con l'avviso per ciascun cliente (difetto C9, audit
     * 28/09/2026: prima la riga restava a database, invisibile, e il totale
     * del cliente scendeva senza una parola). `updated` copre sospensione,
     * modifiche richieste e approvazione revocata; `deleted` la cancellazione.
     *
     * Dopo il boot di tutti i provider: la morph map la registra
     * AppServiceProvider, e le classi si leggono da lì invece di ripeterle.
     * Chi scrive con una query secca salta questi eventi — ed è il caso del
     * ritiro delle smartbox (PartnerPaymentModeService e la migrazione del
     * 27/09, entrambi update di massa): lì la riga esce dal carrello alla
     * prima lettura del cliente, con lo stesso avviso.
     */
    public function boot(): void
    {
        $this->app->booted(function (): void {
            foreach (CartManager::PURCHASABLE_TYPES as $alias) {
                $catalog = Relation::getMorphedModel($alias);

                $catalog::updated(fn (Model $product) => $this->app->make(DatabaseCartStorage::class)->withdrawIfHidden($product));
                $catalog::deleted(fn (Model $product) => $this->app->make(DatabaseCartStorage::class)->withdrawProduct($product));
            }
        });
    }
}
