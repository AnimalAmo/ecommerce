<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class StructureType extends Component
{
    /** Tipologia struttura ricettiva scelta: hotel | bb | agriturismo. */
    public string $type = '';

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:hotel,bb,agriturismo']],
            ['type.required' => __('partner.structure_type.error_required'), 'type.in' => __('partner.structure_type.error_required')],
        );

        // TODO: advance to step 2 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.structure-type')
            ->title(__('partner.structure_type.title'));
    }
}
