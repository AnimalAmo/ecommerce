<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Models\Structure\StructureDraft;
use Livewire\Component;

class ActivityType extends Component
{
    use InteractsWithStructureDraft;

    /** Tipologia scelta per il servizio "Attività ed Eventi": una di StructureDraft::ACTIVITY_TYPES. */
    public string $type = '';

    public function mount(): void
    {
        // La colonna `type` è condivisa col flusso struttura; qui vale attivita|eventi.
        $type = $this->draft()->type;
        $this->type = in_array($type, StructureDraft::ACTIVITY_TYPES, true) ? $type : '';
    }

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:'.implode(',', StructureDraft::ACTIVITY_TYPES)]],
            ['type.required' => __('partner.activity_type.error_required'), 'type.in' => __('partner.activity_type.error_required')],
        );

        // Cambiando ramo i campi dell'altro restavano sulla bozza e finivano in
        // pubblicazione: le colonne solo-struttura arrivando da lì, orari, posti
        // e tipologie passando da Evento ad Attività (un'attività non ha orario
        // di inizio e fine — richiesta della cliente, 29/09/2026), la zona
        // operativa nel verso opposto. Si azzera solo al cambio effettivo:
        // ripassare da questo step senza toccare la scelta non cancella nulla.
        //
        // Difetto F5 (audit del 28/09/2026): l'elenco era scritto qui e, a mano,
        // una seconda volta in StructureType, che era rimasta indietro. Ora la
        // regola è una sola, nel modello (StructureDraft::attributesForType), e
        // riscrive anche la famiglia: una bozza nata struttura che diventa
        // attività pubblica da EventPublisher, non più da StructurePublisher.
        // Cosa resta al cambio, e perché, è scritto su StructureDraft::BRANCH_COLUMNS.
        $this->saveStep($this->draft()->attributesForType($this->type), 1);
        $this->redirectRoute('partner.activity.name');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-type', [
            'backUrl' => $this->serviceChoiceBackUrl(),
        ])->title(__('partner.activity_type.title'));
    }
}
