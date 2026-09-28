<?php

namespace App\Livewire\Concerns;

use App\Enums\ProductType;
use App\Models\Event\Event;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

/**
 * Aggiunta rapida di un evento/attività dalla card di una griglia (nessun pop-up XD:
 * conferma via toast). Condivisa dalla pagina Eventi e dai risultati di ricerca,
 * che nell'XD app mostrano le stesse card con la CTA "Acquista"/"Partecipa".
 */
trait AddsEventToCart
{
    use AddsCatalogProductToCart;

    /**
     * Eventi = 1 partecipante (decisione ratificata); attività = gli stessi
     * default del widget di dettaglio (2 adulti, 1 animale), modificabili
     * poi dal modal del carrello.
     */
    public function addToCart(int $id): void
    {
        $event = Event::findOrFail($id);

        // CTA Partecipa (gratis o senza prezzo): nessun acquisto (partecipazioni allo step 5).
        if ($event->hasJoinCta()) {
            return;
        }

        // Persone da Event::quickAddPersons(): la stessa regola con cui la
        // griglia decide se la borsa si mostra, così click e soglia non divergono.
        $options = $event->type === ProductType::Activity
            ? ['guests' => ['adulti' => $event->quickAddPersons(), 'ragazzi' => 0, 'bambini' => 0], 'animals' => [self::defaultSpecies() => 1]]
            : ['participants' => $event->quickAddPersons()];

        // Titolare che incassa in struttura: si prenota contattando lui, non da
        // qui (richiesta della cliente, 27/09/2026). Nella griglia il pulsante
        // non c'è nemmeno, ma addToCart() arriva dal payload del client: la
        // guardia è in AddsCatalogProductToCart, la stessa delle schede di dettaglio
        // (difetto C7, 28/09/2026), e il perché non stia nel CartManager è
        // scritto lì.
        if (! $this->addCatalogProductToCart($event, $options)) {
            return;
        }

        Flux::toast(text: __('cart.added'), variant: 'success');
    }

    /** Specie di default dell'aggiunta rapida: primo pet dell'utente autenticato, altrimenti cane. */
    private static function defaultSpecies(): string
    {
        return Auth::user()?->pets()->first()?->species ?? 'cane';
    }
}
