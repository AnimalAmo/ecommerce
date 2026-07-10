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
        $this->validate(
            ['price' => ['required', 'string', 'max:32']],
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
