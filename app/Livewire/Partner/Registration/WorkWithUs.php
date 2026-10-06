<?php

namespace App\Livewire\Partner\Registration;

use App\Livewire\Forms\PartnerApplicationForm;
use App\Models\Partner\PartnerApplication;
use App\Models\User;
use App\Services\Partner\RegisterPartnerAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class WorkWithUs extends Component
{
    public PartnerApplicationForm $form;

    /**
     * Un utente ecommerce già registrato trova i propri dati precompilati:
     * l'email resta bloccata sull'account, perché è quella che a fine
     * iscrizione decide quale utente promuovere a partner.
     */
    public function mount(): void
    {
        $this->prefillFromAccount();
    }

    private function prefillFromAccount(): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $application = $user->openPartnerApplication();

        $this->form->fill([
            'firstName' => $application?->first_name ?: $user->first_name,
            'lastName' => $application?->last_name ?: $user->last_name,
            'email' => $user->email,
            'phone' => $application?->phone ?: (string) $user->phone,
            'city' => $application?->city ?: (string) $user->city,
            'website' => (string) $application?->website,
            'businessName' => (string) $application?->business_name,
            'role' => (string) $application?->role,
            'offerType' => (string) $application?->offer_type,
            'description' => (string) $application?->description,
        ]);
    }

    /**
     * Iscrizione diretta (cliente, 06/10/2026): niente più mail d'invito. La
     * candidatura si salva come prima (la cliente la ritrova in «Contatti e
     * candidature»), l'account partner nasce subito e si entra nella propria
     * area. Partita IVA, codice fiscale e indirizzo si completano dal profilo.
     *
     * Il visitatore sceglie la password qui; chi è già loggato diventa partner
     * con il suo account e la sua password. Le schede che creerà passano dalla
     * stessa approvazione di sempre.
     */
    public function submit(RegisterPartnerAccount $registrar): void
    {
        $this->ensureIsNotRateLimited();

        $user = Auth::user();

        // Nessuna scelta all'utente loggato: l'identità è quella dell'account.
        // Il nome va riallineato qui e non solo nel prefill: mount() gira una
        // volta sola, mentre le request successive reidratano lo snapshot.
        if ($user !== null) {
            $this->form->firstName = $user->first_name;
            $this->form->lastName = $user->last_name ?? $this->form->lastName;
            $this->form->email = $user->email;
        }

        $this->form->creatingAccount = $user === null;
        $this->form->validate();

        // Account disattivato: promote() non riattiva nessuno e l'area partner
        // risponderebbe 403. Si dice subito, invece di salvare a metà.
        if ($user !== null && ! $user->is_active) {
            throw ValidationException::withMessages(['form.email' => __('partner.register.error_account_inactive')]);
        }

        $application = $this->persist($this->form->toApplication(), $user);

        $partner = $registrar->register([
            'firstName' => $this->form->firstName,
            'lastName' => $this->form->lastName,
            'businessName' => $this->form->businessName,
            'email' => $this->form->email,
            'phone' => $this->form->phone,
            // Da completare dal profilo: la candidatura non li chiede.
            'address' => null,
            'province' => null,
            'zip' => null,
            'vat' => null,
            'taxCode' => null,
            'password' => $user === null ? $this->form->password : null,
        ], $user, $application->id);

        $partner->partnerProfile()->update(['registration_service' => $this->serviceFor($this->form->offerType)]);

        RateLimiter::hit($this->throttleKey());

        if ($user === null) {
            Auth::login($partner);
            session()->regenerate();
        }

        session()->flash('partner.notice', __('partner.dashboard.joined'));

        $this->redirectRoute('partner.dashboard');
    }

    /**
     * Il tipo di offerta della candidatura preseleziona la card giusta in
     * «Crea servizio» (CreateService legge registration_service). Il select
     * salva l'etichetta nella lingua della pagina, quindi si confronta con quella.
     */
    private function serviceFor(string $offerType): ?string
    {
        return match ($offerType) {
            __('partner.offer_accommodation') => 'struttura',
            __('partner.offer_activities') => 'attivita',
            __('partner.offer_pet_services') => 'servizi',
            default => null,
        };
    }

    /** Cinque iscrizioni al minuto per IP: oltre, l'errore torna sul campo email. */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => __('partner.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate('partner-application|'.request()->ip());
    }

    /**
     * Un reinvio dall'area utente aggiorna la richiesta ancora aperta invece
     * di accodarne una copia: la stessa persona candida la stessa attività.
     * Le candidature da visitatore restano una riga per invio (non c'è un
     * account che le tenga insieme).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function persist(array $attributes, ?User $user): PartnerApplication
    {
        if ($user === null) {
            return PartnerApplication::create($attributes);
        }

        $application = $user->openPartnerApplication();

        if ($application === null) {
            return PartnerApplication::create($attributes + ['user_id' => $user->id]);
        }

        $application->update($attributes);

        return $application;
    }

    public function render()
    {
        return view('livewire.partner.registration.work-with-us')
            ->title(__('partner.title_work_with_us'));
    }
}
