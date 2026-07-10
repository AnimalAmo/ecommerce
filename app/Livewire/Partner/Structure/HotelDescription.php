<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelDescription extends Component
{
    use InteractsWithStructureDraft;

    /** Descrizione della struttura (max 200 caratteri), localizzata: it obbligatoria, en opzionale. */
    public array $description = ['it' => '', 'en' => ''];

    public function mount(): void
    {
        $this->description = array_merge(['it' => '', 'en' => ''], $this->draft()->getTranslations('description'));
    }

    public function next(): void
    {
        $this->validate(
            [
                'description.it' => ['required', 'string', 'max:200'],
                'description.en' => ['nullable', 'string', 'max:200'],
            ],
            ['description.it.required' => __('partner.hotel_description.error_required')],
        );

        // Le traduzioni vuote non vengono salvate: su EN scatta il fallback IT.
        $this->saveStep(['description' => array_filter($this->description, fn ($value) => filled($value))], 4);
        $this->redirectRoute('partner.structure.hotel.rooms');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-description')
            ->title(__('partner.hotel_description.title'));
    }
}
