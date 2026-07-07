<?php

namespace App\Livewire;

use App\Enums\ProductType;
use App\Services\FavoriteService;
use Livewire\Component;

class Favorites extends Component
{
    /** Filtro "Tipologia" attivo dal dropdown sopra il contenitore (value ProductType, null = tutte). */
    public ?string $typeFilter = null;

    /** Id riga favorites già aggiunti al carrello (toggle borsa, solo stato visivo — carrello reale step 3). */
    public array $inCart = [];

    /** Il cuore sulla card rimuove il preferito ($id = riga favorites dell'utente corrente). */
    public function removeFavorite(int $id): void
    {
        auth()->user()?->favorites()->whereKey($id)->delete();

        $this->inCart = array_values(array_diff($this->inCart, [$id]));
    }

    /** Il bottone borsa aggiunge/toglie dal carrello (per ora solo stato visivo). */
    public function toggleCart(int $id): void
    {
        // TODO: carrello reale (step 3).
        if (in_array($id, $this->inCart, true)) {
            $this->inCart = array_values(array_diff($this->inCart, [$id]));
        } else {
            $this->inCart[] = $id;
        }
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

        return view('livewire.favorites', [
            'favorites' => $favorites,
            'visibleFavorites' => $visibleFavorites,
            'types' => $types,
        ])->title('Preferiti — AnimalAmo');
    }
}
