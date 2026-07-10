<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
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

        // Le traduzioni vuote non vengono salvate: su EN scatta il fallback IT.
        $this->saveStep(['name' => array_filter($this->name, fn ($value) => filled($value))], 2);
        $this->redirectRoute('partner.structure.hotel.location');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-title')
            ->title(__('partner.hotel_title.title'));
    }
}
