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
     * Salva la candidatura e invia subito l'email con il link all'iscrizione
     * B2B a step. La moderazione superadmin prevista dalla spec arriverà come
     * gate tra il salvataggio e l'invito (vedi SendPartnerInvitation).
     */
    public function submit(SendPartnerInvitation $invitation): void
    {
        $this->form->validate();

        $application = PartnerApplication::create($this->form->toApplication());

        $invitation->send($application);

        $this->redirectRoute('work-with-us.thanks');
    }

    public function render()
    {
        return view('livewire.partner.registration.work-with-us')
            ->title(__('partner.title_work_with_us'));
    }
}
