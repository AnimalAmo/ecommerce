<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxPrice extends Component
{
    use InteractsWithStructureDraft;

    /** Costo totale della smartbox. */
    public string $price = '';

    public function mount(): void
    {
        $this->price = $this->draft()->price ?? '';
    }

    public function save(): void
    {
        // Numeric (virgola normalizzata sotto): il publisher converte in cents,
        // una stringa libera tipo '215 €' romperebbe il pricing B2C. decimal:0,2
        // respinge anche l'ambiguo '1.500' (migliaia all'italiana ≠ 1.50 €).
        $this->price = str_replace(',', '.', trim($this->price));

        $this->validate(
            ['price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000']],
            ['price.required' => __('partner.smartbox_price.error_required')],
        );

        $this->saveStep(['price' => $this->price], 12);

        // Ultimo step: onboarding smartbox completato (stato -> completed).
        $this->completeDraft(12);
        $this->redirectRoute('partner.dashboard');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-price')
            ->title(__('partner.smartbox_price.title'));
    }
}
