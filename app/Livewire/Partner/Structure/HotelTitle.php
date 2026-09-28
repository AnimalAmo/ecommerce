<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Support\Translations;
use Livewire\Component;

class HotelTitle extends Component
{
    use InteractsWithStructureDraft;

    /** Nome della struttura ricettiva (hotel), localizzato: it obbligatorio, en opzionale. */
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
            ['name.it.required' => __('partner.hotel_title.error_required')],
        );

        // Le traduzioni vuote vanno a null (Translations::replacing, difetto W5):
        // su EN scatta il fallback IT, anche per una lingua tolta dopo averla salvata.
        $this->saveStep(['name' => Translations::replacing($this->name)], 2);
        $this->redirectRoute('partner.structure.hotel.location');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-title')
            ->title(__('partner.hotel_title.title'));
    }
}
