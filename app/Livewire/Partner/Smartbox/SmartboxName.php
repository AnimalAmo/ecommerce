<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxName extends Component
{
    use InteractsWithStructureDraft;

    /** Titolo della smartbox. */
    public string $name = '';

    public function mount(): void
    {
        $this->name = $this->draft()->name ?? '';
    }

    public function next(): void
    {
        $this->validate(
            ['name' => ['required', 'string', 'max:128']],
            ['name.required' => __('partner.smartbox_name.error_required')],
        );

        $this->saveStep(['name' => $this->name], 2);
        $this->redirectRoute('partner.smartbox.description');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-name')
            ->title(__('partner.smartbox_name.title'));
    }
}
