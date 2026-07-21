<?php

namespace App\Livewire\Catalog;

use App\Enums\ProductType;
use App\Livewire\Concerns\AddsEventToCart;
use App\Livewire\Concerns\HasBookingCalendar;
use App\Livewire\Concerns\HasCatalogFilters;
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
    use HasCatalogFilters;
    use TogglesFavorites;
    use WithPagination;

    /** Card per pagina: la griglia XD è 4 colonne × 3 righe. */
    private const PER_PAGE = 12;

    /** Tetto al "Carica altro": oltre non è più una lista ma un dump del catalogo. */
    private const MAX_PER_PAGE = 120;

    /** Card mostrate sotto "Risultati simili alla tua ricerca:" quando i filtri non danno risultati (XD app "Nessun risultato"). */
    private const SIMILAR_LIMIT = 5;

    /** Filtro "Dove": deep-linkabile (?dove=…) e persistente attraverso la paginazione. */
    #[Url(as: 'dove')]
    public string $where = '';

    public string $guests = '';

    public string $animals = '';

    /**
     * Quante card carica la griglia: cresce col "Carica altro" mobile.
     * È una proprietà pubblica, quindi il client può rispedirla alterata nel payload:
     * il valore va sempre riletto tramite pageSize(), mai usato così com'è.
     */
    public int $pageSize = self::PER_PAGE;

    public function search(): void
    {
        // Nuova ricerca "Dove": il filtro è applicato in render(), qui resettiamo la pagina.
        // Il "Quando" (editCheckIn/editCheckOut del datepicker) NON filtra ancora la lista:
        // raccoglie le date per lo step disponibilità (step 6).
        $this->resetPage();
    }

    /** "Carica altro" (XD app): allunga la prima pagina di un blocco invece di impaginare. */
    public function loadMore(): void
    {
        $this->pageSize = $this->pageSize() + self::PER_PAGE;
    }

    public function render()
    {
        // Questa pagina elenca solo attività ed eventi: le chip del modal Filtri le
        // separano per ProductType, nessuna delle due accesa = nessun risultato.
        $eventTypes = array_values(array_filter([
            in_array('attivita', $this->activeTypes, true) ? ProductType::Activity : null,
            in_array('eventi', $this->activeTypes, true) ? ProductType::Event : null,
        ]));

        // Pagina 1 = le 12 card della griglia XD (position); a seguire, paginati,
        // gli eventi finora solo in home (position null, ordinati per home_position).
        $events = ($eventTypes === []
            ? Event::query()->whereRaw('1 = 0')
            : Event::query()->whereIn('type', $eventTypes))
            ->when(trim($this->where) !== '', fn (Builder $query) => $this->applyWhereFilter($query))
            // Fascia di prezzo: attiva solo se l'utente si è mosso dai default XD
            // (i seed hanno anche prezzi a 0 che ai default resterebbero esclusi).
            ->when($this->priceFiltered(), fn (Builder $query) => $query->whereBetween('price_cents', $this->priceRangeCents()))
            ->orderByRaw('position is null')
            ->orderBy('position')
            ->orderBy('home_position')
            ->paginate($this->pageSize());

        $empty = $events->isEmpty();

        // "Nessun risultato trovato": l'XD app non lascia la pagina vuota ma propone card
        // simili, cioè lo stesso catalogo senza i filtri (tipologia e prezzo) che l'hanno svuotato.
        $similar = $empty
            ? Event::query()
                ->when(trim($this->where) !== '', fn (Builder $query) => $this->applyWhereFilter($query))
                ->orderByRaw('position is null')
                ->orderBy('position')
                ->orderBy('home_position')
                ->limit(self::SIMILAR_LIMIT)
                ->get()
            : $events->getCollection();

        return view('livewire.catalog.events', [
            'events' => $events,
            'empty' => $empty,
            'similar' => $similar,
            // Datepicker "Quando": calendario range condiviso (giorni passati disabilitati).
            'calendar' => $this->buildCalendar(),
            'calendarLabel' => $this->calendarLabel(),
        ])->title(__('events.meta_title'));
    }

    /** Solo attività ed eventi: le altre card tipologia restano nel modal per fedeltà XD ma non filtrano qui. */
    protected function defaultFilterTypes(): array
    {
        return ['attivita', 'eventi'];
    }

    /** Cambiare un filtro stando su una pagina avanzata atterrerebbe su una pagina vuota. */
    protected function onFiltersChanged(): void
    {
        $this->resetPage();
    }

    /** Dimensione pagina bonificata: il payload client è arbitrario, la teniamo tra un blocco e il tetto. */
    private function pageSize(): int
    {
        return max(self::PER_PAGE, min($this->pageSize, self::MAX_PER_PAGE));
    }

    /** Filtro "Dove" (trim, case-insensitive): titolo OR location OR nome della venue. */
    private function applyWhereFilter(Builder $query): Builder
    {
        $term = self::like(trim($this->where));

        return $query->where(function (Builder $sub) use ($term): void {
            // title è JSON translatable: LIKE sul path del locale corrente.
            $sub->whereLike('title->'.app()->getLocale(), $term)
                ->orWhereLike('location', $term)
                ->orWhereHas('venue', fn (Builder $venue) => $venue->whereLike('name', $term));
        });
    }
}
