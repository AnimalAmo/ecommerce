<?php

namespace App\Livewire\Partner\Activity;

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

        $this->saveStep(['type' => $this->type, ...$this->clearedFields()], 1);
        $this->redirectRoute('partner.activity.name');
    }

    /**
     * Cambiando ramo i campi dell'altro restavano sulla bozza e finivano in
     * pubblicazione: le colonne solo-struttura arrivando da lì, gli orari
     * passando da Evento ad Attività (un'attività non ha orario di inizio e
     * fine — richiesta della cliente, 29/09/2026). Il pannello admin questa
     * correzione ce l'ha già in ActivityCreate::updatedType(), il wizard no.
     *
     * Si azzera solo al cambio effettivo: ripassare da questo step senza
     * toccare la scelta non deve cancellare il lavoro già fatto.
     *
     * @return array<string, null>
     */
    private function clearedFields(): array
    {
        $previous = $this->draft()->type;

        if ($previous === $this->type) {
            return [];
        }

        $cleared = [];

        // Da Struttura ricettiva: le colonne che solo quel percorso riempie.
        if ($previous !== null && ! in_array($previous, ['attivita', 'eventi'], true)) {
            $cleared = array_fill_keys(
                ['license', 'rooms', 'checkin_from', 'checkin_to', 'checkout_from', 'checkout_to', 'smartbox_consent', 'smartbox_types'],
                null,
            );
        }

        // Da Evento ad Attività: gli orari non esistono più su questo ramo.
        if ($this->type === 'attivita') {
            $cleared['time_start'] = null;
            $cleared['time_end'] = null;
        }

        return $cleared;
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-type', [
            'backUrl' => $this->serviceChoiceBackUrl(),
        ])->title(__('partner.activity_type.title'));
    }
}
