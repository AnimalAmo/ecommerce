<?php

namespace App\Livewire\Catalog;

use App\Livewire\Concerns\HasBookingCalendar;
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

    // News ancora mock: il backend news arriva con lo step 5 della roadmap.
    // Excerpt vuoti: copy in attesa della cliente (la card degrada a foto+data+titolo+link).
    public array $news = [
        ['img' => 'news-trenitalia', 'date' => '20 Ottobre 2023', 'title' => 'Novità Trenitalia trasporto animali', 'excerpt' => ''],
        ['img' => 'news-easyjet', 'date' => '3 Ottobre 2023', 'title' => 'Novità EasyJet trasporto animali', 'excerpt' => ''],
        ['img' => 'event-cavallo', 'date' => '5 Ottobre 2025', 'title' => 'Viaggiare in montagna con il cane', 'excerpt' => ''],
    ];

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
            'regions' => Region::whereNotNull('home_position')->orderBy('home_position')->get(),
            // Suggerimenti del pannello Destinazione (modal filtri mobile): stesso set
            // filtrabile della listing holiday (LIKE sul nome regione), max 6 voci come da XD.
            'destinations' => Region::query()
                ->when(trim($this->where) !== '', fn ($query) => $query->whereLike('name', '%'.addcslashes(trim($this->where), '\%_').'%'))
                ->orderBy('position')
                ->limit(6)
                ->pluck('name'),
            'events' => Event::whereNotNull('home_position')->orderBy('home_position')->get(),
            // Datepicker "Quando" nella hero: calendario range condiviso (giorni passati disabilitati).
            'calendar' => $this->buildCalendar(),
            'calendarLabel' => $this->calendarLabel(),
        ])->title(__('home.meta_title'));
    }
}
