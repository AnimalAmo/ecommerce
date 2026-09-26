<?php

namespace App\Livewire\Partner\Structure;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class StructureType extends Component
{
    use InteractsWithStructureDraft;

    /** Tipologia struttura ricettiva scelta: hotel | bb | agriturismo | casa_vacanza. */
    public string $type = '';

    /** Le quattro categorie di questo percorso: nessun'altra va precaricata nel radio. */
    private const TYPES = ['hotel', 'bb', 'agriturismo', 'casa_vacanza'];

    public function mount(): void
    {
        // Stessa guardia di ActivityType::mount(). La colonna `type` è condivisa
        // fra i due percorsi: senza filtro, una bozza che veniva da
        // Attività/Eventi precaricherebbe qui un valore che nessuna card mostra
        // e che il validatore rifiuta — il partner vedrebbe un errore su una
        // scelta che non ha fatto.
        $type = $this->draft()->type;

        $this->type = in_array($type, self::TYPES, true) ? $type : '';
    }

    public function next(): void
    {
        $this->validate(
            ['type' => ['required', 'string', 'in:'.implode(',', self::TYPES)]],
            ['type.required' => __('partner.structure_type.error_required'), 'type.in' => __('partner.structure_type.error_required')],
        );

        $this->saveStep(['type' => $this->type, ...$this->clearedFields()], 1);
        $this->redirectRoute('partner.structure.hotel.title');
    }

    /**
     * Passando da Attività/Eventi a una struttura ricettiva, i campi dell'altro
     * ramo restavano scritti sulla bozza e finivano in pubblicazione. Si
     * azzerano solo al cambio effettivo di ramo: tornare su questo step senza
     * cambiare nulla non deve cancellare il lavoro già fatto.
     *
     * @return array<string, null>
     */
    private function clearedFields(): array
    {
        $draft = $this->draft();

        if ($draft->type === null || in_array($draft->type, self::TYPES, true)) {
            return [];
        }

        return array_fill_keys(
            ['meeting_point', 'date_start', 'date_end', 'time_start', 'time_end', 'detailed_description'],
            null,
        );
    }

    public function render()
    {
        return view('livewire.partner.structure.structure-type', [
            'backUrl' => $this->serviceChoiceBackUrl(),
        ])->title(__('partner.structure_type.title'));
    }
}
