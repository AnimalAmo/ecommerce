<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\HandlesAnimalServicesStep;
use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityAnimalServices extends Component
{
    use HandlesAnimalServicesStep, InteractsWithStructureDraft;

    public function render()
    {
        return view('livewire.partner.activity.activity-animal-services')
            ->title(__('partner.activity_animal_services.title'));
    }

    protected function animalServicesStep(): int
    {
        return 7;
    }

    protected function animalServicesNextRoute(): string
    {
        return 'partner.activity.cost';
    }
}
