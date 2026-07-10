<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityDescription extends Component
{
    use InteractsWithStructureDraft;

    /** Descrizione breve ("Presenta la tua attività"), max 200, localizzata: it obbligatorio, en opzionale. */
    public array $description = ['it' => '', 'en' => ''];

    /** Descrizione dettagliata (solo per le Attività), max 200, localizzata: it obbligatorio, en opzionale. */
    public array $detailedDescription = ['it' => '', 'en' => ''];

    /** Le Attività hanno anche la descrizione dettagliata; gli Eventi no. */
    public bool $isActivity = false;

    public function mount(): void
    {
        $draft = $this->draft();
        $this->description = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('description'));
        $this->detailedDescription = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('detailed_description'));
        $this->isActivity = $draft->type === 'attivita';
    }

    public function next(): void
    {
        $rules = [
            'description.it' => ['required', 'string', 'max:200'],
            'description.en' => ['nullable', 'string', 'max:200'],
        ];
        if ($this->isActivity) {
            $rules['detailedDescription.it'] = ['required', 'string', 'max:200'];
            $rules['detailedDescription.en'] = ['nullable', 'string', 'max:200'];
        }

        $this->validate($rules, [
            'description.it.required' => __('partner.activity_description.error_required'),
            'detailedDescription.it.required' => __('partner.activity_description.error_required'),
        ]);

        // Le traduzioni vuote non vengono salvate: su EN scatta il fallback IT.
        $attributes = ['description' => array_filter($this->description, fn ($value) => filled($value))];
        if ($this->isActivity) {
            $attributes['detailed_description'] = array_filter($this->detailedDescription, fn ($value) => filled($value));
        }

        $this->saveStep($attributes, 4);
        $this->redirectRoute('partner.activity.info');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-description')
            ->title(__('partner.activity_description.title'));
    }
}
