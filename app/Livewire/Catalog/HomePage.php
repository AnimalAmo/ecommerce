<?php

namespace App\Livewire\Catalog;

use App\Livewire\Concerns\HasBookingCalendar;
use App\Models\Article\Article;
use App\Models\Community\CommunityPost;
use App\Models\Event\Event;
use App\Models\Region\Region;
use Livewire\Component;

class HomePage extends Component
{
    // Datepicker "Quando": riusa la macchina del calendario range (editCheckIn/editCheckOut).
    // mountHasBookingCalendar() è invocato automaticamente da Livewire come hook di mount del trait
    // (stesso schema di AnimalHolidayStructure): punta il calendario al mese corrente.
    use HasBookingCalendar;

    public string $where = '';

    /** Tipologia scelta nel modal filtri mobile ('' = nessuna → default Hotel e servizi). */
    public string $type = '';

    /** Tipologie del pannello (XD app: Hotel e servizi / Eventi e attività / Smartbox). */
    public const SEARCH_TYPES = ['hotel', 'eventi', 'smartbox'];

    /** Il pannello Animali del modal mostra cani e gatti (XD app). */
    public function mount(): void
    {
        $this->editAnimals = ['cane' => 0, 'gatto' => 0];
    }

    /** Card news della home: le tre più recenti di Animal Times, come da XD. */
    private const HOME_NEWS = 3;

    /** Card eventi della home: la griglia XD è a 5 colonne su una riga sola. */
    private const HOME_EVENTS = 5;

    /** Tap su un suggerimento del pannello Destinazione (modal filtri mobile). */
    public function selectDestination(string $name): void
    {
        $this->where = $name;
    }

    /** Tap su una voce del pannello Tipologia (modal filtri mobile). */
    public function selectType(string $type): void
    {
        if (in_array($type, self::SEARCH_TYPES, true)) {
            $this->type = $type;
        }
    }

    public function search()
    {
        // Submit hero: redirect alla listing con Dove + le date del datepicker.
        // La Tipologia del modal mobile decide la destinazione: Eventi e attività →
        // listing eventi (legge ?dove), Smartbox → listing smartbox (nessun filtro
        // in query), default → Animal Holiday (legge ?dove e filtra le regioni).
        // Checkin/checkout viaggiano per lo step disponibilità (step 6); ospiti e
        // animali del modal restano UI-only finché le listing non li consumano.
        $params = array_filter([
            'dove' => trim($this->where),
            'checkin' => $this->editCheckIn,
            'checkout' => $this->editCheckOut,
        ], fn ($value): bool => $value !== null && $value !== '');

        return match ($this->type) {
            'eventi' => $this->redirectRoute('eventi', array_intersect_key($params, ['dove' => true]), navigate: true),
            'smartbox' => $this->redirectRoute('smartbox', navigate: true),
            default => $this->redirectRoute('holiday', $params, navigate: true),
        };
    }

    public function render()
    {
        return view('livewire.catalog.home-page', [
            // Conteggio VERO delle strutture pubblicate nella regione: la colonna
            // regions.structures_count è ferma ai numeri dell'artboard XD (10/5/7) e
            // col catalogo reale direbbe il falso. Alias esplicito perché withCount()
            // userebbe di default proprio il nome della colonna congelata, e quale
            // dei due valori sopravvive alla fetch dipenderebbe dall'ordine del SELECT.
            'regions' => Region::query()
                ->withCount(['structures as published_structures_count'])
                ->whereNotNull('home_position')
                ->orderBy('home_position')
                ->get(),
            // Suggerimenti del pannello Destinazione (modal filtri mobile): stesso set
            // filtrabile della listing holiday (LIKE sul nome regione), max 6 voci come da XD.
            'destinations' => Region::query()
                ->when(trim($this->where) !== '', fn ($query) => $query->whereLike('name', '%'.addcslashes(trim($this->where), '\%_').'%'))
                ->orderBy('position')
                ->limit(6)
                ->pluck('name'),
            // Eventi in vetrina. NON più filtrati su home_position: quella colonna la
            // scrive solo l'EventSeeder del mock XD, EventPublisher (pubblicazione
            // partner) valorizza `position` — quindi la fascia non si sarebbe mai
            // potuta riempire con eventi veri. Regola: prima i più imminenti.
            'events' => Event::query()
                // Un evento già iniziato non è più proponibile; le attività senza data
                // puntuale (starts_at null) restano valide, stessa regola di
                // AvailabilityService::ensureEventAvailable().
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '>=', now()))
                // I più vicini per primi; le attività senza data chiudono la fila.
                ->orderByRaw('starts_at is null')
                ->orderBy('starts_at')
                ->orderBy('position')
                ->limit(self::HOME_EVENTS)
                ->get(),
            'news' => Article::published()->take(self::HOME_NEWS)->get(),
            // Fascia Animal Network: il post più recente davvero pubblicato. Il box
            // XD mostrava un post inventato (Sofia, 25/11/23, 6 risposte) che a
            // bacheca vuota era l'unico "post" visibile sul sito.
            'communityPost' => CommunityPost::query()
                ->withCount('replies')
                ->latest('created_at')
                ->latest('id')
                ->first(),
            // Datepicker "Quando" nella hero: calendario range condiviso (giorni passati disabilitati).
            'calendar' => $this->buildCalendar(),
            'calendarLabel' => $this->calendarLabel(),
        ])->title(__('home.meta_title'));
    }
}
