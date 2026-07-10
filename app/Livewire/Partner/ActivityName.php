<?php

namespace App\Livewire\Partner;

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

        // TODO: advance to step 3 of 10 ("attività/eventi - luogo") once it exists.
    }

    public function render()
    {
        return view('livewire.partner.activity-name')
            ->title(__('partner.activity_name.title'));
    }
}
