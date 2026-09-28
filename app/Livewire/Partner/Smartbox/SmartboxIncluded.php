<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SmartboxIncluded extends Component
{
    use InteractsWithStructureDraft;

    /** Servizi struttura inclusi nella smartbox (multi-scelta). */
    public array $included = [];

    /**
     * Rilettura filtrata sugli slug del gruppo `services`, come
     * HotelServicesForm::setFromDraft(): uno slug che la vista non disegna
     * bloccherebbe lo step sull'`in:` di next(), senza una casella da togliere.
     */
    public function mount(): void
    {
        $this->included = array_values(array_intersect(
            $this->draft()->included_services ?? [],
            ServiceOptionLabels::slugs('services'),
        ));
    }

    public function next(): void
    {
        // Ogni voce contro la mappa da cui la vista la disegna (gruppo
        // `services`, lo stesso dei servizi struttura), come fa già il pannello
        // admin: uno slug inventato non arriva alla bozza.
        $this->validate([
            'included' => ['array'],
            'included.*' => ['string', Rule::in(ServiceOptionLabels::slugs('services'))],
        ]);

        $this->saveStep(['included_services' => $this->included], 8);
        $this->redirectRoute('partner.smartbox.included-animals');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-included')
            ->title(__('partner.smartbox_included.title'));
    }
}
