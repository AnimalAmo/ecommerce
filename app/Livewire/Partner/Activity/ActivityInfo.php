<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Concerns\ProvidesTimeSlots;
use App\Livewire\Forms\ActivityInfoForm;
use Livewire\Component;

class ActivityInfo extends Component
{
    use InteractsWithStructureDraft, ProvidesTimeSlots;

    public ActivityInfoForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 5);
        $this->redirectRoute('partner.activity.included');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-info', ['times' => $this->times()])
            ->title(__('partner.activity_info.title'));
    }
}
