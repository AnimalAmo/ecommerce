<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelServices extends Component
{
    /** Servizi struttura selezionati (multi-scelta). */
    public array $services = [];

    /** Servizi aggiuntivi presenti (multi-scelta). */
    public array $additional = [];

    /** Dettaglio per "Altro" servizio aggiuntivo. */
    public string $additionalOther = '';

    /** Orari (inizio/fine) per i pasti: colazione | pranzo | cena. */
    public array $mealTimes = [
        'colazione' => ['from' => '', 'to' => ''],
        'pranzo' => ['from' => '', 'to' => ''],
        'cena' => ['from' => '', 'to' => ''],
    ];

    /** Regole della struttura (multi-scelta). */
    public array $rules = [];

    /** Orari selezionabili (mezz'ora): 00:00 → 23:30. */
    public function times(): array
    {
        $times = [];
        for ($h = 0; $h < 24; $h++) {
            $times[] = sprintf('%02d:00', $h);
            $times[] = sprintf('%02d:30', $h);
        }

        return $times;
    }

    public function next(): void
    {
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string'],
            'additional' => ['array'],
            'additional.*' => ['string'],
            'additionalOther' => ['nullable', 'string', 'max:200'],
            'mealTimes.*.from' => ['nullable', 'string'],
            'mealTimes.*.to' => ['nullable', 'string'],
            'rules' => ['array'],
            'rules.*' => ['string'],
        ]);

        // TODO: advance to step 8 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-services', ['times' => $this->times()])
            ->title(__('partner.hotel_services.title'));
    }
}
