<?php

namespace App\Livewire\Catalog;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use App\Services\Cart\CartManager;
use Flux\Flux;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Events extends Component
{
    // Datepicker "Quando": mountHasBookingCalendar() è invocato in automatico da Livewire
    // (hook di mount del trait), punta il calendario range al mese corrente.
    use HasBookingCalendar;
    use TogglesFavorites;
    use WithPagination;

    /** Card per pagina: la griglia XD è 4 colonne × 3 righe. */
    private const PER_PAGE = 12;

    /** Filtro "Dove": deep-linkabile (?dove=…) e persistente attraverso la paginazione. */
    #[Url(as: 'dove')]
    public string $where = '';

    public string $guests = '';

    public string $animals = '';

    public function search(): void
    {
        // Nuova ricerca "Dove": il filtro è applicato in render(), qui resettiamo la pagina.
        // Il "Quando" (editCheckIn/editCheckOut del datepicker) NON filtra ancora la lista:
        // raccoglie le date per lo step disponibilità (step 6).
        $this->resetPage();
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

        $this->dispatch('cart-updated');
        Flux::toast(text: __('cart.added'), variant: 'success');
    }

    /** Specie di default dell'aggiunta rapida: primo pet dell'utente autenticato, altrimenti cane. */
    private static function defaultSpecies(): string
    {
        return Auth::user()?->pets()->first()?->species ?? 'cane';
    }

    public function render()
    {
        return view('livewire.catalog.events', [
            // Pagina 1 = le 12 card della griglia XD (position); a seguire, paginati,
            // gli eventi finora solo in home (position null, ordinati per home_position).
            'events' => Event::query()
                ->when(trim($this->where) !== '', fn (Builder $query) => $this->applyWhereFilter($query))
                ->orderByRaw('position is null')
                ->orderBy('position')
                ->orderBy('home_position')
                ->paginate(self::PER_PAGE),
            // Datepicker "Quando": calendario range condiviso (giorni passati disabilitati).
            'calendar' => $this->buildCalendar(),
            'calendarLabel' => $this->calendarLabel(),
        ])->title(__('events.meta_title'));
    }

    /** Filtro "Dove" (trim, case-insensitive): titolo OR location OR nome della venue. */
    private function applyWhereFilter(Builder $query): Builder
    {
        $term = '%'.addcslashes(trim($this->where), '\%_').'%';

        return $query->where(function (Builder $sub) use ($term): void {
            $sub->whereLike('title', $term)
                ->orWhereLike('location', $term)
                ->orWhereHas('venue', fn (Builder $venue) => $venue->whereLike('name', $term));
        });
    }
}
