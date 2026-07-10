<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\HandlesAnimalServicesStep;
use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelAnimalServices extends Component
{
    use HandlesAnimalServicesStep, InteractsWithStructureDraft;

    public function render()
    {
        return view('livewire.partner.structure.hotel-animal-services')
            ->title(__('partner.hotel_animal_services.title'));
    }

    protected function animalServicesStep(): int
    {
        return 8;
    }

    protected function animalServicesNextRoute(): string
    {
        return 'partner.structure.hotel.smartbox';
    }
}
