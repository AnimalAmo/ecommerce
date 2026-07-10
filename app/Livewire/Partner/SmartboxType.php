<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class SmartboxType extends Component
{
    use InteractsWithStructureDraft;

    /** Tipologia smartbox scelta: soggiorno | benessere | avventura. */
    public string $type = '';

    public function mount(): void
    {
        // La colonna `type` è condivisa; per lo smartbox vale soggiorno|benessere|avventura.
        $type = $this->draft()->type;
        $this->type = in_array($type, ['soggiorno', 'benessere', 'avventura'], true) ? $type : '';
    }

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:soggiorno,benessere,avventura']],
            ['type.required' => __('partner.smartbox_type.error_required'), 'type.in' => __('partner.smartbox_type.error_required')],
        );

        $this->saveStep(['type' => $this->type], 1);

        // TODO: advance to step 2 of 12 of the smartbox creation flow once it exists.
    }

    public function render()
    {
        return view('livewire.partner.smartbox-type')
            ->title(__('partner.smartbox_type.title'));
    }
}
