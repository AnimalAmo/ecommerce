<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Support\Translations;
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

        // Le traduzioni vuote vanno a null (Translations::replacing, difetto W5):
        // su EN scatta il fallback IT, anche per una lingua tolta dopo averla salvata.
        $this->saveStep(['name' => Translations::replacing($this->name)], 2);
        $this->redirectRoute('partner.smartbox.description');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-name')
            ->title(__('partner.smartbox_name.title'));
    }
}
