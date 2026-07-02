<?php

namespace App\Livewire;

use App\Livewire\Forms\PartnerApplicationForm;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Lavora con noi')]
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
        return view('livewire.work-with-us');
    }
}
