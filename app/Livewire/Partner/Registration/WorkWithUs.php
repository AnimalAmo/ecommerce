<?php

namespace App\Livewire\Partner\Registration;

use App\Livewire\Forms\PartnerApplicationForm;
use App\Models\Partner\PartnerApplication;
use App\Models\User;
use App\Services\Partner\SendPartnerInvitation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WorkWithUs extends Component
{
    public PartnerApplicationForm $form;

    /**
     * L'XD app conferma con una modale sopra il form, l'XD desktop con la
     * thank-you page: il viewport lo sa solo il client, che alza il flag in
     * x-init prima dell'invio.
     */
    public bool $confirmInPlace = false;

    public bool $showConfirmation = false;

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
     * Salva la candidatura e invia subito l'email con il link all'iscrizione
     * B2B a step. La moderazione superadmin prevista dalla spec arriverà come
     * gate tra il salvataggio e l'invito (vedi SendPartnerInvitation).
     */
    public function submit(SendPartnerInvitation $invitation): void
    {
        $user = Auth::user();

        // Nessuna scelta all'utente loggato: l'email è quella dell'account.
        if ($user !== null) {
            $this->form->email = $user->email;
        }

        $this->form->validate();

        $application = $this->persist($this->form->toApplication(), $user);

        $invitation->send($application);

        if ($this->confirmInPlace) {
            $this->showConfirmation = true;

            return;
        }

        $this->redirectRoute('work-with-us.thanks');
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

    /**
     * Chiudendo la modale il form riparte vuoto: la candidatura è già salvata.
     * All'utente loggato tornano i dati del suo account (e della richiesta
     * appena inviata), non un form vuoto che non potrebbe ricompilare.
     */
    public function closeConfirmation(): void
    {
        $this->showConfirmation = false;

        $this->form->reset();

        $this->prefillFromAccount();
    }

    public function render()
    {
        return view('livewire.partner.registration.work-with-us')
            ->title(__('partner.title_work_with_us'));
    }
}
