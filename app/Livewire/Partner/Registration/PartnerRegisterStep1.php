<?php

namespace App\Livewire\Partner\Registration;

use App\Livewire\Forms\PartnerRegistrationForm;
use App\Models\Partner\PartnerApplication;
use App\Models\Region\Province;
use Illuminate\Http\Request;
use Livewire\Component;

class PartnerRegisterStep1 extends Component
{
    public PartnerRegistrationForm $form;

    /**
     * Arrivando dal link firmato dell'email di invito, i dati anagrafici
     * della candidatura precompilano il form; un ritorno dallo step 2
     * ripristina invece quanto già inserito (sessione).
     */
    public function mount(Request $request): void
    {
        if ($saved = session('partner_registration.step1')) {
            $this->form->fill($saved);

            return;
        }

        if ($request->query('application') && $request->hasValidSignature()) {
            $application = PartnerApplication::find($request->query('application'));

            if ($application !== null) {
                session(['partner_registration.application_id' => $application->id]);
                $this->form->fill([
                    'firstName' => $application->first_name,
                    'lastName' => $application->last_name,
                    'businessName' => $application->business_name,
                    'email' => $application->email,
                    'phone' => $application->phone,
                ]);
            }
        }
    }

    /** Valida e parcheggia i dati in sessione: l'account nasce a fine step 2. */
    public function submit(): void
    {
        $this->form->validate();

        session(['partner_registration.step1' => $this->form->all()]);

        $this->redirectRoute('partner.register.step2');
    }

    public function render()
    {
        return view('livewire.partner.registration.register-step1', [
            'provinces' => Province::orderBy('name')->get(),
        ])->title(__('partner.register.title'));
    }
}
