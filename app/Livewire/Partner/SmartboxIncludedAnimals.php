<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxIncludedAnimals extends Component
{
    use InteractsWithStructureDraft;

    /** Servizi dedicati agli animali inclusi (multi-scelta). */
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

        $this->saveStep(['animal_services' => $this->services, 'animal_services_other' => $this->other], 9);

        // TODO: advance to step 10 of 12 (foto) del flusso smartbox once it exists.
    }

    public function render()
    {
        return view('livewire.partner.smartbox-included-animals')
            ->title(__('partner.smartbox_included_animals.title'));
    }
}
