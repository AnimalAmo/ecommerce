<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxName extends Component
{
    use InteractsWithStructureDraft;

    /** Titolo della smartbox, localizzato: it obbligatorio, en opzionale. */
    public array $name = ['it' => '', 'en' => ''];

    public function mount(): void
    {
        $this->name = array_merge(['it' => '', 'en' => ''], $this->draft()->getTranslations('name'));
    }

    public function next(): void
    {
        $this->validate(
            [
                'name.it' => ['required', 'string', 'max:128'],
                'name.en' => ['nullable', 'string', 'max:128'],
            ],
            ['name.it.required' => __('partner.smartbox_name.error_required')],
        );

        // Le traduzioni vuote non vengono salvate: su EN scatta il fallback IT.
        $this->saveStep(['name' => array_filter($this->name, fn ($value) => filled($value))], 2);
        $this->redirectRoute('partner.smartbox.description');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-name')
            ->title(__('partner.smartbox_name.title'));
    }
}
