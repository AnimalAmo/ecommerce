<?php

namespace App\Livewire\Partner;

use App\Livewire\Forms\PartnerApplicationForm;
use Livewire\Component;

class WorkWithUs extends Component
{
    public PartnerApplicationForm $form;

    public function submit(): void
    {
        // TODO: validate and persist the application once the backend exists.
        $this->redirectRoute('work-with-us.thanks');
    }

    public function render()
    {
        return view('livewire.partner.work-with-us')
            ->title(__('partner.title_work_with_us'));
    }
}
