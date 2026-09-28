<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Validation\Rule;
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

        // Solo gli slug che lo step disegna, come gli altri step (review di WP8,
        // 28/09/2026): con la whitelist in next(), uno slug estraneo già in
        // bozza bloccava «Avanti» senza un messaggio né una casella da togliere.
        $this->amenities = array_values(array_intersect($draft->services ?? [], ServiceOptionLabels::slugs('smartbox_amenities')));
        $this->additional = array_values(array_intersect($draft->additional_services ?? [], ServiceOptionLabels::slugs('smartbox_additional')));
    }

    public function next(): void
    {
        // Whitelist come negli altri step (WP8, 28/09/2026): `additional_services`
        // arriva al pivot delle amenity, e uno slug della mappa scritto a mano
        // nel payload sarebbe finito sulla scheda.
        $this->validate([
            'amenities' => ['array'],
            'amenities.*' => ['string', Rule::in(ServiceOptionLabels::slugs('smartbox_amenities'))],
            'additional' => ['array'],
            'additional.*' => ['string', Rule::in(ServiceOptionLabels::slugs('smartbox_additional'))],
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
