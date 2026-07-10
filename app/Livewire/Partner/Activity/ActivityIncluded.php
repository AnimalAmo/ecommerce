<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Concerns\ProvidesTimeSlots;
use App\Livewire\Forms\ActivityIncludedForm;
use Livewire\Component;

class ActivityIncluded extends Component
{
    use InteractsWithStructureDraft, ProvidesTimeSlots;

    public ActivityIncludedForm $form;

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 6);
        $this->redirectRoute('partner.activity.animal-services');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-included', ['times' => $this->times()])
            ->title(__('partner.activity_included.title'));
    }
}
