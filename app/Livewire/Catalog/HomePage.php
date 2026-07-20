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

    // News ancora mock: il backend news arriva con lo step 5 della roadmap.
    public array $news = [
        ['img' => 'news-trenitalia', 'date' => '20 Ottobre 2023', 'title' => 'Novità Trenitalia trasporto animali', 'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
        ['img' => 'news-easyjet', 'date' => '3 Ottobre 2023', 'title' => 'Novità EasyJet trasporto animali', 'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
        ['img' => 'event-cavallo', 'date' => '5 Ottobre 2025', 'title' => 'Viaggiare in montagna con il cane', 'excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'],
    ];

    /** Tap su un suggerimento del pannello Destinazione (modal filtri mobile). */
    public function selectDestination(string $name): void
    {
        $this->where = $name;
    }

    public function search()
    {
        // Submit hero: redirect alla listing Animal Holiday con Dove + le date del datepicker.
        // AnimalHoliday legge ?dove e filtra le regioni; checkin/checkout viaggiano per lo
        // step disponibilità (step 6) — qui la home non filtra nulla in loco.
        return $this->redirectRoute('holiday', array_filter([
            'dove' => trim($this->where),
            'checkin' => $this->editCheckIn,
            'checkout' => $this->editCheckOut,
        ], fn ($value): bool => $value !== null && $value !== ''), navigate: true);
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
