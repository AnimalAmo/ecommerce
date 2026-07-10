<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class StructureType extends Component
{
    use InteractsWithStructureDraft;

    /** Tipologia struttura ricettiva scelta: hotel | bb | agriturismo. */
    public string $type = '';

    public function mount(): void
    {
        $this->type = $this->draft()->type ?? '';
    }

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:hotel,bb,agriturismo']],
            ['type.required' => __('partner.structure_type.error_required'), 'type.in' => __('partner.structure_type.error_required')],
        );

        $this->saveStep(['type' => $this->type], 1);
        $this->redirectRoute('partner.structure.hotel.title');
    }

    public function render()
    {
        return view('livewire.partner.structure.structure-type')
            ->title(__('partner.structure_type.title'));
    }
}
