<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Support\Translations;
use Livewire\Component;

class SmartboxDescription extends Component
{
    use InteractsWithStructureDraft;

    /** Descrizione breve ("Presenta la tua smartbox"), max 200, localizzata: it obbligatoria, en opzionale. */
    public array $description = ['it' => '', 'en' => ''];

    /** Descrizione dettagliata ("Descrivi in modo dettagliato la smartbox"), max 200, localizzata: it obbligatoria, en opzionale. */
    public array $detailedDescription = ['it' => '', 'en' => ''];

    public function mount(): void
    {
        $draft = $this->draft();
        $this->description = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('description'));
        $this->detailedDescription = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('detailed_description'));
    }

    public function next(): void
    {
        $this->validate([
            'description.it' => ['required', 'string', 'max:200'],
            'description.en' => ['nullable', 'string', 'max:200'],
            'detailedDescription.it' => ['required', 'string', 'max:200'],
            'detailedDescription.en' => ['nullable', 'string', 'max:200'],
        ], [
            'description.it.required' => __('partner.smartbox_description.error_required'),
            'detailedDescription.it.required' => __('partner.smartbox_description.error_required'),
        ]);

        // Le traduzioni vuote vanno a null (Translations::replacing, difetto W5):
        // su EN scatta il fallback IT, anche per una lingua tolta dopo averla salvata.
        $this->saveStep([
            'description' => Translations::replacing($this->description),
            'detailed_description' => Translations::replacing($this->detailedDescription),
        ], 3);
        $this->redirectRoute('partner.smartbox.duration');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-description')
            ->title(__('partner.smartbox_description.title'));
    }
}
