<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelDescription extends Component
{
    use InteractsWithStructureDraft;

    /** Descrizione della struttura (max 200 caratteri). */
    public string $description = '';

    public function mount(): void
    {
        $this->description = $this->draft()->description ?? '';
    }

    public function next(): void
    {
        $this->validate(
            ['description' => ['required', 'string', 'max:200']],
            ['description.required' => __('partner.hotel_description.error_required')],
        );

        $this->saveStep(['description' => $this->description], 4);
        $this->redirectRoute('partner.structure.hotel.rooms');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-description')
            ->title(__('partner.hotel_description.title'));
    }
}
