<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelSmartbox extends Component
{
    /** Consenso all'inserimento nelle smartbox: si | no (default si, come XD). */
    public string $consent = 'si';

    /** Tipologie di servizio che aderiscono alle smartbox (multi-scelta, solo se consent=si). */
    public array $types = [];

    public function next(): void
    {
        $this->validate([
            'consent' => ['required', 'in:si,no'],
            'types' => ['array'],
            'types.*' => ['string'],
        ]);

        // TODO: advance to step 10 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-smartbox')
            ->title(__('partner.hotel_smartbox.title'));
    }
}
