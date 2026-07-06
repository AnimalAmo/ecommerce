<?php

namespace App\Livewire;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Services\Cart\CartManager;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('AnimalAmo — Attività ed Eventi')]
class Events extends Component
{
    use TogglesFavorites;
    use WithPagination;

    /** Card per pagina: la griglia XD è 4 colonne × 3 righe. */
    private const PER_PAGE = 12;

    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    public function search(): void
    {
        // TODO: filter events once the listing backend exists.
    }

    /**
     * Aggiunta rapida dalla card griglia (nessun pop-up XD: conferma via toast).
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

        Flux::toast(text: __('cart.added'), variant: 'success');
    }

    /** Specie di default dell'aggiunta rapida: primo pet dell'utente autenticato, altrimenti cane. */
    private static function defaultSpecies(): string
    {
        return Auth::user()?->pets()->first()?->species ?? 'cane';
    }

    public function render()
    {
        return view('livewire.events', [
            // Pagina 1 = le 12 card della griglia XD (position); a seguire, paginati,
            // gli eventi finora solo in home (position null, ordinati per home_position).
            'events' => Event::orderByRaw('position is null')
                ->orderBy('position')
                ->orderBy('home_position')
                ->paginate(self::PER_PAGE),
        ]);
    }
}
