<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelTitle extends Component
{
    /** Nome della struttura ricettiva (hotel). */
    public string $name = '';

    public function next(): void
    {
        $this->validate(
            ['name' => ['required', 'string', 'max:128']],
            ['name.required' => __('partner.hotel_title.error_required')],
        );

        // TODO: advance to step 3 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-title')
            ->title(__('partner.hotel_title.title'));
    }
}
