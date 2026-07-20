<?php

namespace App\Livewire\Catalog;

use App\Livewire\Concerns\AddsEventToCart;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Events extends Component
{
    // Datepicker "Quando": mountHasBookingCalendar() è invocato in automatico da Livewire
    // (hook di mount del trait), punta il calendario range al mese corrente.
    use AddsEventToCart;
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
            // title è JSON translatable: LIKE sul path del locale corrente.
            $sub->whereLike('title->'.app()->getLocale(), $term)
                ->orWhereLike('location', $term)
                ->orWhereHas('venue', fn (Builder $venue) => $venue->whereLike('name', $term));
        });
    }
}
