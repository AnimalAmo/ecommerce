<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Models\Structure\StructureDraft;
use Livewire\Component;

class StructureType extends Component
{
    use InteractsWithStructureDraft;

    /** Tipologia struttura ricettiva scelta: una di StructureDraft::STRUCTURE_TYPES. */
    public string $type = '';

    public function mount(): void
    {
        // Stessa guardia di ActivityType::mount(). La colonna `type` è condivisa
        // fra i due percorsi: senza filtro, una bozza che veniva da
        // Attività/Eventi precaricherebbe qui un valore che nessuna card mostra
        // e che il validatore rifiuta — il partner vedrebbe un errore su una
        // scelta che non ha fatto.
        $type = $this->draft()->type;

        $this->type = in_array($type, StructureDraft::STRUCTURE_TYPES, true) ? $type : '';
    }

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:'.implode(',', StructureDraft::STRUCTURE_TYPES)]],
            ['type.required' => __('partner.structure_type.error_required'), 'type.in' => __('partner.structure_type.error_required')],
        );

        // Passando da Attività/Eventi a una struttura ricettiva, i campi
        // dell'altro ramo restavano scritti sulla bozza e finivano in
        // pubblicazione. Difetto F5 (audit del 28/09/2026): l'elenco da azzerare
        // era scritto qui a mano e non era stato esteso alle colonne del
        // 26-27/09, e la categoria del servizio non si riscriveva, così la bozza
        // pubblicava ancora da EventPublisher coi posti dell'evento abbandonato.
        // La regola, una sola per questo step e per ActivityType, sta nel modello.
        $this->saveStep($this->draft()->attributesForType($this->type), 1);
        $this->redirectRoute('partner.structure.hotel.title');
    }

    public function render()
    {
        return view('livewire.partner.structure.structure-type', [
            'backUrl' => $this->serviceChoiceBackUrl(),
        ])->title(__('partner.structure_type.title'));
    }
}
