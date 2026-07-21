<?php

namespace App\Livewire\Partner\Registration;

use App\Livewire\Forms\PartnerApplicationForm;
use App\Models\Partner\PartnerApplication;
use App\Services\Partner\SendPartnerInvitation;
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
     * Salva la candidatura e invia subito l'email con il link all'iscrizione
     * B2B a step. La moderazione superadmin prevista dalla spec arriverà come
     * gate tra il salvataggio e l'invito (vedi SendPartnerInvitation).
     */
    public function submit(SendPartnerInvitation $invitation): void
    {
        $this->form->validate();

        $application = PartnerApplication::create($this->form->toApplication());

        $invitation->send($application);

        if ($this->confirmInPlace) {
            $this->showConfirmation = true;

            return;
        }

        $this->redirectRoute('work-with-us.thanks');
    }

    /**
     * Chiudendo la modale il form riparte vuoto: la candidatura è già salvata.
     */
    public function closeConfirmation(): void
    {
        $this->showConfirmation = false;

        $this->form->reset();
    }

    public function render()
    {
        return view('livewire.partner.registration.work-with-us')
            ->title(__('partner.title_work_with_us'));
    }
}
