<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxOffers extends Component
{
    use InteractsWithStructureDraft;

    /** Cosa troverai nell'alloggio (multi-scelta). */
    public array $amenities = [];

    /** Servizi aggiuntivi presenti (multi-scelta). */
    public array $additional = [];

    public function mount(): void
    {
        $draft = $this->draft();
        $this->amenities = $draft->services ?? [];
        $this->additional = $draft->additional_services ?? [];
    }

    public function next(): void
    {
        $this->validate([
            'amenities' => ['array'],
            'amenities.*' => ['string'],
            'additional' => ['array'],
            'additional.*' => ['string'],
        ]);

        $this->saveStep([
            'services' => $this->amenities,
            'additional_services' => $this->additional,
        ], 7);

        // TODO: advance to step 8 of 12 del flusso smartbox once it exists.
    }

    public function render()
    {
        return view('livewire.partner.smartbox-offers')
            ->title(__('partner.smartbox_offers.title'));
    }
}
