<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityName extends Component
{
    use InteractsWithStructureDraft;

    /** Nome dell'attività/evento. */
    public string $name = '';

    public function mount(): void
    {
        $this->name = $this->draft()->name ?? '';
    }

    public function next(): void
    {
        $this->validate(
            ['name' => ['required', 'string', 'max:128']],
            ['name.required' => __('partner.activity_name.error_required')],
        );

        $this->saveStep(['name' => $this->name], 2);
        $this->redirectRoute('partner.activity.location');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-name')
            ->title(__('partner.activity_name.title'));
    }
}
