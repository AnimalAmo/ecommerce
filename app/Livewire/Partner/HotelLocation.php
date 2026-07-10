<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelLocation extends Component
{
    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    public string $license = '';

    public function next(): void
    {
        $this->validate([
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zip' => ['required', 'digits:5'],
            'license' => ['required', 'string', 'max:64'],
        ]);

        // TODO: advance to step 4 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-location')
            ->title(__('partner.hotel_location.title'));
    }
}
