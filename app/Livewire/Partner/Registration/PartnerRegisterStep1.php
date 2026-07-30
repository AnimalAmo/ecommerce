<?php

namespace App\Livewire\Partner\Registration;

use App\Livewire\Auth\AuthModal;
use App\Livewire\Forms\PartnerRegistrationForm;
use App\Models\Partner\PartnerApplication;
use App\Models\Region\Province;
use App\Models\User;
use Flux\Flux;
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
     * L'email inserita è di un altro account: accanto all'errore compare
     * l'accesso, che è la sola via d'uscita (l'iscrizione promuove quell'account).
     */
    public bool $emailConflict = false;

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

        $this->resolveEmailConflict($user);
    }

    /**
     * Ritorno dallo step 2 perché l'email è di un ALTRO account: l'errore si
     * mostra sul campo che lo genera, con l'invito ad accedere.
     *
     * Se nel frattempo l'utente ha fatto login il conflitto è chiuso — l'email
     * ora è quella del suo account, che l'iscrizione promuoverà — e si riprende
     * dallo step 2 senza fargli ricompilare quello che è già in sessione.
     */
    private function resolveEmailConflict(?User $user): void
    {
        $conflict = session('partner_registration.email_conflict');

        if ($conflict === null) {
            return;
        }

        if ($user !== null) {
            session()->forget('partner_registration.email_conflict');

            if (session('partner_registration.step1') !== null) {
                $this->redirectRoute('partner.register.step2');
            }

            return;
        }

        $this->emailConflict = true;
        $this->form->email = $conflict;

        $this->addError('form.email', __('partner.register.error_email_taken'));
    }

    /**
     * Apre la modale di login con l'email già scritta: all'utente resta la
     * password. Dopo il login si rientra su questa pagina (url()->previous())
     * e `resolveEmailConflict()` rimanda allo step 2.
     */
    public function loginToContinue(): void
    {
        $this->dispatch('prefill-login-email', email: $this->form->email)->to(AuthModal::class);

        Flux::modal('login')->show();
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

        // L'unica email ricontrollata qui è quella già rimbalzata dallo step 2:
        // non rivela nulla di nuovo (l'esistenza dell'account l'abbiamo appena
        // detta) ed evita il ping-pong fra i due step. Le altre restano senza
        // `unique:users`, che senza throttle sarebbe un oracolo di enumerazione
        // (cfr. Auth\RegisterModal, dove lo step 1 è protetto da RateLimiter).
        if ($this->emailStillTaken()) {
            $this->emailConflict = true;

            $this->addError('form.email', __('partner.register.error_email_taken'));

            return;
        }

        session()->forget('partner_registration.email_conflict');
        session(['partner_registration.step1' => $this->form->all()]);

        $this->redirectRoute('partner.register.step2');
    }

    /** L'email riproposta dopo il rimbalzo è ancora quella di un altro account? */
    private function emailStillTaken(): bool
    {
        $conflict = session('partner_registration.email_conflict');

        return Auth::guest()
            && $conflict !== null
            && strcasecmp($conflict, $this->form->email) === 0
            && User::where('email', $this->form->email)->exists();
    }

    public function render()
    {
        return view('livewire.partner.registration.register-step1', [
            'provinces' => Province::orderBy('name')->get(),
        ])->title(__('partner.register.title'));
    }
}
