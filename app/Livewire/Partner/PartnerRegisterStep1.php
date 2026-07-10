<?php

namespace App\Livewire\Partner;

use App\Livewire\Forms\PartnerRegistrationForm;
use Livewire\Component;

class PartnerRegisterStep1 extends Component
{
    public PartnerRegistrationForm $form;

    public function submit(): void
    {
        $this->form->validate();

        // TODO: persist step-1 data and advance to "Iscrizione B2B - step 2"
        // once that page and the partner backend exist.
    }

    public function render()
    {
        return view('livewire.partner.register-step1')
            ->title(__('partner.register.title'));
    }
}
