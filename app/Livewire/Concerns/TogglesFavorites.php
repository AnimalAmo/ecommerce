<?php

namespace App\Livewire\Concerns;

use App\Services\FavoriteService;
use Flux\Flux;
use Livewire\Attributes\Computed;

/**
 * Cuore preferiti condiviso dalle pagine catalogo: adapter Livewire sottile su
 * FavoriteService (toggle persistente per l'utente autenticato, modale login
 * per l'ospite). Le viste inizializzano lo stato ottimistico Alpine da
 * favoritedKeys (una sola query per pagina).
 */
trait TogglesFavorites
{
    /** Aggiunge/toglie il preferito ($type = alias morph); da ospite apre il login. */
    public function toggleFavorite(string $type, int $id): void
    {
        if (! auth()->check()) {
            Flux::modal('login')->show();

            return;
        }

        app(FavoriteService::class)->toggle(auth()->user(), $type, $id);

        unset($this->favoritedKeys);
    }

    /**
     * Chiavi "alias:id" dei preferiti dell'utente corrente per idratare i cuori.
     *
     * @return list<string>
     */
    #[Computed]
    public function favoritedKeys(): array
    {
        if (! auth()->check()) {
            return [];
        }

        return app(FavoriteService::class)->favoritedKeys(auth()->user());
    }

    /** True se il prodotto ($type = alias morph) è tra i preferiti dell'utente corrente. */
    public function isFavorite(string $type, int $id): bool
    {
        return in_array($type.':'.$id, $this->favoritedKeys, true);
    }
}
