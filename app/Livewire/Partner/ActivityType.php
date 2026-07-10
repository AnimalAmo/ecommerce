<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class ActivityType extends Component
{
    use InteractsWithStructureDraft;

    /** Tipologia scelta per il servizio "Attività ed Eventi": attivita | eventi. */
    public string $type = '';

    public function mount(): void
    {
        // La colonna `type` è condivisa col flusso struttura; qui vale attivita|eventi.
        $type = $this->draft()->type;
        $this->type = in_array($type, ['attivita', 'eventi'], true) ? $type : '';
    }

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:attivita,eventi']],
            ['type.required' => __('partner.activity_type.error_required'), 'type.in' => __('partner.activity_type.error_required')],
        );

        $this->saveStep(['type' => $this->type], 1);

        // TODO: advance to step 2 of 10 ("tipologia attività/eventi - nome") once it exists.
    }

    public function render()
    {
        return view('livewire.partner.activity-type')
            ->title(__('partner.activity_type.title'));
    }
}
