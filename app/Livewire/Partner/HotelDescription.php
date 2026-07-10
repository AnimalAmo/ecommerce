<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelDescription extends Component
{
    /** Descrizione della struttura (max 200 caratteri). */
    public string $description = '';

    public function next(): void
    {
        $this->validate(
            ['description' => ['required', 'string', 'max:200']],
            ['description.required' => __('partner.hotel_description.error_required')],
        );

        // TODO: advance to step 5 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-description')
            ->title(__('partner.hotel_description.title'));
    }
}
