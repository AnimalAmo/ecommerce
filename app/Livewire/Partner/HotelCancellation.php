<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class HotelCancellation extends Component
{
    use InteractsWithStructureDraft;

    /** Giorni prima dell'arrivo entro cui la cancellazione è gratuita: 30 | 15 | 7 | 1. */
    public string $when = '1';

    /** Percentuale (0–100) di barra "verde" (cancellazione gratuita) in base alla scelta. */
    private const GREEN = ['30' => 17, '15' => 35, '7' => 53, '1' => 68];

    public function mount(): void
    {
        $this->when = $this->draft()->cancellation_when ?: '1';
    }

    public function next(): void
    {
        $this->validate(
            ['when' => ['required', 'in:30,15,7,1']],
            ['when.required' => __('partner.hotel_cancellation.error_required'), 'when.in' => __('partner.hotel_cancellation.error_required')],
        );

        $this->saveStep(['cancellation_when' => $this->when], 6);
        $this->redirectRoute('partner.structure.hotel.services');
    }

    public function render()
    {
        return view('livewire.partner.hotel-cancellation', [
            'green' => self::GREEN[$this->when] ?? 68,
        ])->title(__('partner.hotel_cancellation.title'));
    }
}
