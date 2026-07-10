<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxDescription extends Component
{
    use InteractsWithStructureDraft;

    /** Descrizione breve ("Presenta la tua smartbox"), max 200. */
    public string $description = '';

    /** Descrizione dettagliata ("Descrivi in modo dettagliato la smartbox"), max 200. */
    public string $detailedDescription = '';

    public function mount(): void
    {
        $draft = $this->draft();
        $this->description = $draft->description ?? '';
        $this->detailedDescription = $draft->detailed_description ?? '';
    }

    public function next(): void
    {
        $this->validate([
            'description' => ['required', 'string', 'max:200'],
            'detailedDescription' => ['required', 'string', 'max:200'],
        ], [
            'description.required' => __('partner.smartbox_description.error_required'),
            'detailedDescription.required' => __('partner.smartbox_description.error_required'),
        ]);

        $this->saveStep([
            'description' => $this->description,
            'detailed_description' => $this->detailedDescription,
        ], 3);
        $this->redirectRoute('partner.smartbox.duration');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-description')
            ->title(__('partner.smartbox_description.title'));
    }
}
