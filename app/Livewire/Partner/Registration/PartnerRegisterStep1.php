<?php

namespace App\Livewire\Partner\Registration;

use App\Livewire\Forms\PartnerRegistrationForm;
use App\Models\Region\Province;
use Livewire\Component;

class PartnerRegisterStep1 extends Component
{
    public PartnerRegistrationForm $form;

    public function submit(): void
    {
        $this->form->validate();

        // TODO: persist step-1 data once the partner backend exists.
        $this->redirectRoute('partner.register.step2');
    }

    public function render()
    {
        return view('livewire.partner.registration.register-step1', [
            'provinces' => Province::orderBy('name')->get(),
        ])->title(__('partner.register.title'));
    }
}
