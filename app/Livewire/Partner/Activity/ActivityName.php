<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityName extends Component
{
    use InteractsWithStructureDraft;

    /** Nome dell'attività/evento, localizzato: it obbligatorio, en opzionale. */
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
            ['name.it.required' => __('partner.activity_name.error_required')],
        );

        // Le traduzioni vuote non vengono salvate: su EN scatta il fallback IT.
        $this->saveStep(['name' => array_filter($this->name, fn ($value) => filled($value))], 2);
        $this->redirectRoute('partner.activity.location');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-name')
            ->title(__('partner.activity_name.title'));
    }
}
