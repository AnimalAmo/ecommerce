<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxDuration extends Component
{
    use InteractsWithStructureDraft;

    /** Durata della smartbox in giorni. */
    public ?int $durationDays = null;

    public function mount(): void
    {
        $this->durationDays = $this->draft()->duration_days;
    }

    public function next(): void
    {
        $this->validate([
            'durationDays' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'durationDays.required' => __('partner.smartbox_duration.error_required'),
            'durationDays.integer' => __('partner.smartbox_duration.error_required'),
            'durationDays.min' => __('partner.smartbox_duration.error_min'),
            'durationDays.max' => __('partner.smartbox_duration.error_max'),
        ]);

        $this->saveStep(['duration_days' => $this->durationDays], 4);
        $this->redirectRoute('partner.smartbox.cancellation');
    }

    public function render()
    {
        return view('livewire.partner.smartbox-duration')
            ->title(__('partner.smartbox_duration.title'));
    }
}
