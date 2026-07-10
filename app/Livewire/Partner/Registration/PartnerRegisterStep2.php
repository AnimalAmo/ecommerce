<?php

namespace App\Livewire\Partner\Registration;

use Livewire\Component;

class PartnerRegisterStep2 extends Component
{
    /** Tipo di servizio scelto (radio, scelta singola): struttura | attivita | servizi. */
    public string $service = '';

    public function createAccount(): void
    {
        $this->validate(
            ['service' => ['required', 'string', 'in:struttura,attivita,servizi']],
            ['service.required' => __('partner.register2.error_required'), 'service.in' => __('partner.register2.error_required')],
        );

        // TODO: persist step-1 + step-2 data and create the partner account
        // once the partner backend exists.
    }

    public function render()
    {
        return view('livewire.partner.registration.register-step2')
            ->title(__('partner.register.title'));
    }
}
