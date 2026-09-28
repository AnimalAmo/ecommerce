<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SmartboxIncludedAnimals extends Component
{
    use InteractsWithStructureDraft;

    /** Servizi dedicati agli animali inclusi (multi-scelta). */
    public array $services = [];

    /** Dettaglio per "Altro". */
    public string $other = '';

    /**
     * Rilettura filtrata sugli slug del gruppo `animal_services`, come
     * HandlesAnimalServicesStep: uno slug che la vista non disegna
     * bloccherebbe lo step sull'`in:` di next(), senza una casella da togliere.
     */
    public function mount(): void
    {
        $draft = $this->draft();
        $this->services = array_values(array_intersect(
            $draft->animal_services ?? [],
            ServiceOptionLabels::slugs('animal_services'),
        ));
        $this->other = $draft->animal_services_other ?? '';
    }

    public function next(): void
    {
        // Ogni voce contro la mappa da cui la vista la disegna, come fa già il
        // pannello admin: uno slug inventato non arriva alla bozza.
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string', Rule::in(ServiceOptionLabels::slugs('animal_services'))],
            'other' => ['nullable', 'string', 'max:200'],
        ]);

        $this->saveStep(['animal_services' => $this->services, 'animal_services_other' => $this->other], 9);
        $this->redirectRoute('partner.smartbox.structures');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-included-animals')
            ->title(__('partner.smartbox_included_animals.title'));
    }
}
