<?php

namespace App\Livewire\Commerce;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\TogglesFavorites;
use App\Services\FavoriteService;
use Flux\Flux;
use Livewire\Component;

class Favorites extends Component
{
    // I cuori delle card "più amate" dello stato vuoto (solo mobile) sono quelli del catalogo.
    use TogglesFavorites;

    /** Filtro "Tipologia" attivo dal dropdown sopra il contenitore (value ProductType, null = tutte). */
    public ?string $typeFilter = null;

    /** Id riga favorites già aggiunti al carrello (toggle borsa, solo stato visivo — carrello reale step 3). */
    public array $inCart = [];

    /** Il cuore sulla card rimuove il preferito ($id = riga favorites dell'utente corrente). */
    public function removeFavorite(int $id): void
    {
        auth()->user()?->favorites()->whereKey($id)->delete();

        $this->inCart = array_values(array_diff($this->inCart, [$id]));

        unset($this->favoritedKeys);
    }

    /**
     * Il bottone borsa aggiunge il preferito al carrello con le opzioni di
     * default della sua famiglia (delegato a FavoriteService::addToCart) e ne
     * marca lo stato visivo. Ospite → modale login (come il cuore preferiti).
     * Già in carrello = no-op: l'add del carrello è idempotente e la card non
     * rimuove (semplificazione ratificata — la rimozione vive nel carrello).
     */
    public function toggleCart(int $id): void
    {
        if (! auth()->check()) {
            Flux::modal('login')->show();

            return;
        }

        if (in_array($id, $this->inCart, true)) {
            return;
        }

        try {
            $added = app(FavoriteService::class)->addToCart(auth()->user(), $id);
        } catch (CartValidationException $exception) {
            Flux::toast(text: $exception->getMessage(), variant: 'danger');

            return;
        }

        // Prodotto non acquistabile (evento gratuito): nessuna riga, nessun feedback.
        if (! $added) {
            return;
        }

        $this->inCart[] = $id;
        $this->dispatch('cart-updated');
        Flux::toast(text: __('cart.added'), variant: 'success');
    }

    /** Selezione dal menu "Tipologia"; null (voce "Tutte") azzera il filtro. La coerenza col contenuto è garantita in render(). */
    public function setTypeFilter(?string $type): void
    {
        $this->typeFilter = ProductType::tryFrom($type ?? '')?->value;
    }

    public function render()
    {
        $service = app(FavoriteService::class);

        // Preferiti dell'utente autenticato nel contratto della card condivisa; ospite → [] (stato vuoto).
        $favorites = auth()->check() ? $service->cards(auth()->user()) : [];
        $types = $service->availableTypes($favorites);

        // Filtro riallineato al contenuto: rimosso l'ultimo preferito di una tipologia,
        // il filtro decade su "Tutte" invece di lasciare un "Nessun risultato" fantasma.
        $activeFilter = in_array(ProductType::tryFrom($this->typeFilter ?? ''), $types, true)
            ? $this->typeFilter
            : null;

        // Filtro tipologia sulla lista corrente (match sul tipo della card).
        $visibleFavorites = $activeFilter === null
            ? $favorites
            : array_values(array_filter(
                $favorites,
                fn (array $item): bool => $item['type'] === $activeFilter,
            ));

        return view('livewire.commerce.favorites', [
            'favorites' => $favorites,
            'visibleFavorites' => $visibleFavorites,
            'types' => $types,
            // Card "Le attività più amate" sotto lo stato vuoto (artboard app "Preferiti - vuoti").
            'suggestions' => $favorites === [] ? $service->topFavorited() : [],
        ])->title(__('favorites.page_title'));
    }
}
