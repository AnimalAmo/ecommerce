<?php

namespace App\Livewire\Partner\Smartbox;

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
        $this->redirectRoute('partner.smartbox.included');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-offers')
            ->title(__('partner.smartbox_offers.title'));
    }
}
