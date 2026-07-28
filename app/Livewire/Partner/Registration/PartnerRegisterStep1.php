<?php

namespace App\Livewire\Partner\Registration;

use App\Livewire\Forms\PartnerRegistrationForm;
use App\Models\Partner\PartnerApplication;
use App\Models\Region\Province;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PartnerRegisterStep1 extends Component
{
    public PartnerRegistrationForm $form;

    /**
     * L'utente ecommerce loggato non sceglie l'email: è quella del suo account,
     * ed è ciò che a fine iscrizione decide quale utente promuovere a partner.
     */
    public bool $emailLocked = false;

    /**
     * Tre modi di arrivare qui, in ordine di precedenza:
     *   1. ritorno dallo step 2 → si ripristina quanto già inserito (sessione);
     *   2. link firmato dell'email di invito → prefill dalla candidatura;
     *   3. utente loggato con una richiesta ancora aperta → prefill dalla sua
     *      candidatura (l'identità la prova la sessione, non serve la firma).
     */
    public function mount(Request $request): void
    {
        $user = Auth::user();
        $this->emailLocked = $user !== null;

        if ($saved = session('partner_registration.step1')) {
            $this->form->fill($saved);
        } elseif ($application = $this->applicationFrom($request, $user)) {
            session(['partner_registration.application_id' => $application->id]);
            $this->form->fill([
                'firstName' => $application->first_name,
                'lastName' => $application->last_name,
                'businessName' => $application->business_name,
                'email' => $application->email,
                'phone' => $application->phone,
            ]);
        } elseif ($user !== null) {
            $this->form->fill([
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'phone' => (string) $user->phone,
                'address' => (string) $user->address,
                'zip' => (string) $user->postal_code,
            ]);
        }

        if ($user !== null) {
            $this->form->email = $user->email;
        }
    }

    /**
     * La candidatura da cui precompilare. Il link firmato vale solo se la
     * candidatura non è di un ALTRO account: altrimenti un utente loggato
     * completerebbe l'iscrizione con i dati di qualcun altro.
     */
    private function applicationFrom(Request $request, ?User $user): ?PartnerApplication
    {
        if ($request->query('application') && $request->hasValidSignature()) {
            $application = PartnerApplication::find($request->query('application'));

            if ($application !== null && ($application->user_id === null || $application->user_id === $user?->id)) {
                return $application;
            }
        }

        return $user?->openPartnerApplication();
    }

    /** Valida e parcheggia i dati in sessione: l'account nasce a fine step 2. */
    public function submit(): void
    {
        if ($user = Auth::user()) {
            $this->form->email = $user->email;
        }

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
