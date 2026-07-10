<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelSmartbox extends Component
{
    use InteractsWithStructureDraft;

    /** Consenso all'inserimento nelle smartbox: si | no (default si, come XD). */
    public string $consent = 'si';

    /** Tipologie di servizio che aderiscono alle smartbox (multi-scelta, solo se consent=si). */
    public array $types = [];

    public function mount(): void
    {
        $draft = $this->draft();
        $this->consent = $draft->smartbox_consent ?: 'si';
        $this->types = $draft->smartbox_types ?? [];
    }

    public function next(): void
    {
        $this->validate([
            'consent' => ['required', 'in:si,no'],
            'types' => ['array'],
            'types.*' => ['string'],
        ]);

        $this->saveStep(['smartbox_consent' => $this->consent, 'smartbox_types' => $this->types], 9);
        $this->redirectRoute('partner.structure.hotel.photos');
    }

    public function render()
    {
        return view('livewire.partner.structure.hotel-smartbox')
            ->title(__('partner.hotel_smartbox.title'));
    }
}
