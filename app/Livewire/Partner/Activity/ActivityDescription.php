<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityDescription extends Component
{
    use InteractsWithStructureDraft;

    /** Descrizione breve ("Presenta la tua attività"), max 200. */
    public string $description = '';

    /** Descrizione dettagliata (solo per le Attività), max 200. */
    public string $detailedDescription = '';

    /** Le Attività hanno anche la descrizione dettagliata; gli Eventi no. */
    public bool $isActivity = false;

    public function mount(): void
    {
        $draft = $this->draft();
        $this->description = $draft->description ?? '';
        $this->detailedDescription = $draft->detailed_description ?? '';
        $this->isActivity = $draft->type === 'attivita';
    }

    public function next(): void
    {
        $rules = ['description' => ['required', 'string', 'max:200']];
        if ($this->isActivity) {
            $rules['detailedDescription'] = ['required', 'string', 'max:200'];
        }

        $this->validate($rules, [
            'description.required' => __('partner.activity_description.error_required'),
            'detailedDescription.required' => __('partner.activity_description.error_required'),
        ]);

        $attributes = ['description' => $this->description];
        if ($this->isActivity) {
            $attributes['detailed_description'] = $this->detailedDescription;
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
