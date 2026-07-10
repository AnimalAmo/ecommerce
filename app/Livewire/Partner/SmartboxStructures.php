<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxStructures extends Component
{
    use InteractsWithStructureDraft;

    /** Strutture incluse nella smartbox (multi-scelta). */
    public array $structures = [];

    /**
     * Strutture del partner selezionabili nella smartbox.
     * Dati demo finché non esiste l'inventario reale delle strutture partner.
     */
    public const OPTIONS = [
        'hotel_milano' => ['name' => 'Hotel Milano', 'city' => 'Milano'],
        'hotel_residence' => ['name' => 'Hotel Residence', 'city' => 'Santa Margherita Ligure'],
        'hotel_brescia' => ['name' => 'Hotel Brescia', 'city' => 'Dario Boario Terme (BS)'],
        'hotel_mantova' => ['name' => 'Hotel Mantova Residence', 'city' => 'Mantova'],
        'lamasu' => ['name' => 'Lamasu W&R', 'city' => 'San Felice del Benaco (BS)'],
    ];

    public function mount(): void
    {
        $this->structures = $this->draft()->smartbox_structures ?? [];
    }

    public function next(): void
    {
        $this->validate([
            'structures' => ['array'],
            'structures.*' => ['string', 'in:'.implode(',', array_keys(self::OPTIONS))],
        ]);

        $this->saveStep(['smartbox_structures' => $this->structures], 10);
        $this->redirectRoute('partner.smartbox.photos');
    }

    public function render()
    {
        return view('livewire.partner.smartbox-structures', ['options' => self::OPTIONS])
            ->title(__('partner.smartbox_structures.title'));
    }
}
