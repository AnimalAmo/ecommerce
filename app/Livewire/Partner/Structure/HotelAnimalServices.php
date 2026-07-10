<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelAnimalServices extends Component
{
    use InteractsWithStructureDraft;

    /** Servizi dedicati agli animali selezionati (multi-scelta). */
    public array $services = [];

    /** Dettaglio per "Altro". */
    public string $other = '';

    public function mount(): void
    {
        $draft = $this->draft();
        $this->services = $draft->animal_services ?? [];
        $this->other = $draft->animal_services_other ?? '';
    }

    public function next(): void
    {
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string'],
            'other' => ['nullable', 'string', 'max:200'],
        ]);

        $this->saveStep(['animal_services' => $this->services, 'animal_services_other' => $this->other], 8);
        $this->redirectRoute('partner.structure.hotel.smartbox');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-animal-services')
            ->title(__('partner.hotel_animal_services.title'));
    }
}
