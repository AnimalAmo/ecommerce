<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Forms\ActivityLocationForm;
use Livewire\Component;

class ActivityLocation extends Component
{
    use InteractsWithStructureDraft;

    public ActivityLocationForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 3);
        $this->redirectRoute('partner.activity.description');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-location')
            ->title(__('partner.activity_location.title'));
    }
}
