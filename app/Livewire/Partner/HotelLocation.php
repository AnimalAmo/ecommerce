<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelLocation extends Component
{
    use InteractsWithStructureDraft;

    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    public string $license = '';

    public function mount(): void
    {
        $this->address = $this->draft()->address ?? '';
        $this->city = $this->draft()->city ?? '';
        $this->province = $this->draft()->province ?? '';
        $this->zip = $this->draft()->zip ?? '';
        $this->license = $this->draft()->license ?? '';
    }

    public function next(): void
    {
        $this->validate([
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zip' => ['required', 'digits:5'],
            'license' => ['required', 'string', 'max:64'],
        ]);

        $this->saveStep(['address' => $this->address, 'city' => $this->city, 'province' => $this->province, 'zip' => $this->zip, 'license' => $this->license], 3);
        $this->redirectRoute('partner.structure.hotel.description');
    }

    public function render()
    {
        return view('livewire.partner.hotel-location')
            ->title(__('partner.hotel_location.title'));
    }
}
