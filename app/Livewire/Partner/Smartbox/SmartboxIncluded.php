<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxIncluded extends Component
{
    use InteractsWithStructureDraft;

    /** Servizi struttura inclusi nella smartbox (multi-scelta). */
    public array $included = [];

    public function mount(): void
    {
        $this->included = $this->draft()->included_services ?? [];
    }

    public function next(): void
    {
        $this->validate([
            'included' => ['array'],
            'included.*' => ['string'],
        ]);

        $this->saveStep(['included_services' => $this->included], 8);
        $this->redirectRoute('partner.smartbox.included-animals');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-included')
            ->title(__('partner.smartbox_included.title'));
    }
}
