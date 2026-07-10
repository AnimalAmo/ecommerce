<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class CreateService extends Component
{
    /** Tipo di servizio scelto (radio, scelta singola). */
    public string $service = '';

    public function next(): void
    {
        $this->validate(
            ['service' => ['required', 'string', 'in:struttura,attivita,servizi,smartbox']],
            ['service.required' => __('partner.create_service.error_required'), 'service.in' => __('partner.create_service.error_required')],
        );

        // TODO: advance to the service-type-specific creation flow once it exists.
    }

    public function render()
    {
        return view('livewire.partner.create-service')
            ->title(__('partner.create_service.title'));
    }
}
