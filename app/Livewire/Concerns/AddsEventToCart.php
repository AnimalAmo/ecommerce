<?php

namespace App\Livewire\Concerns;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Services\Cart\CartManager;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

/**
 * Aggiunta rapida di un evento/attività dalla card di una griglia (nessun pop-up XD:
 * conferma via toast). Condivisa dalla pagina Eventi e dai risultati di ricerca,
 * che nell'XD app mostrano le stesse card con la CTA "Acquista"/"Partecipa".
 */
trait AddsEventToCart
{
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

        $options = $event->type === ProductType::Activity
            ? ['guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0], 'animals' => [self::defaultSpecies() => 1]]
            : ['participants' => 1];

        try {
            app(CartManager::class)->addItem('event', $event->id, $options, false);
        } catch (CartValidationException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        $this->dispatch('cart-updated');
        Flux::toast(text: __('cart.added'), variant: 'success');
    }

    /** Specie di default dell'aggiunta rapida: primo pet dell'utente autenticato, altrimenti cane. */
    private static function defaultSpecies(): string
    {
        return Auth::user()?->pets()->first()?->species ?? 'cane';
    }
}
