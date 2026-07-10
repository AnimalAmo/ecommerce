<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class PartnerRegisterStep2 extends Component
{
    /** Tipi di servizio selezionati (multi-scelta): struttura | attivita | servizi. */
    public array $services = [];

    public function toggle(string $service): void
    {
        if (in_array($service, $this->services, true)) {
            $this->services = array_values(array_diff($this->services, [$service]));

            return;
        }

        $this->services[] = $service;
    }

    public function createAccount(): void
    {
        $this->validate(
            ['services' => ['required', 'array', 'min:1']],
            ['services.required' => __('partner.register2.error_required'), 'services.min' => __('partner.register2.error_required')],
        );

        // TODO: persist step-1 + step-2 data and create the partner account
        // once the partner backend exists.
    }

    public function render()
    {
        return view('livewire.partner.register-step2')
            ->title(__('partner.register.title'));
    }
}
