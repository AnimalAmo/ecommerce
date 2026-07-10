<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelAnimalServices extends Component
{
    /** Servizi dedicati agli animali selezionati (multi-scelta). */
    public array $services = [];

    /** Dettaglio per "Altro". */
    public string $other = '';

    public function next(): void
    {
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string'],
            'other' => ['nullable', 'string', 'max:200'],
        ]);

        // TODO: advance to step 9 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-animal-services')
            ->title(__('partner.hotel_animal_services.title'));
    }
}
