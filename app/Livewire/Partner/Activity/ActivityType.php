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
     * Con i campi nati dalle risposte della cliente del 27/09/2026 la regola
     * resta la stessa, applicata a una lista più lunga. Quello che NON si
     * azzera, e perché:
     *   - `activity_categories` e il suo testo libero: sono l'identità del
     *     professionista, non dell'attività, e il publisher già non le porta
     *     sugli eventi veri. Azzerarle farebbe ricompilare a un maneggio che
     *     apre un evento la risposta che aveva già dato.
     *   - `booking_requirement`: la cliente la chiede a entrambi i rami, quindi
     *     la risposta resta valida dopo il cambio.
     *   - `date_start` e `date_end`: anche le attività possono averle.
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

        // Verso Attività: si abbandona il ramo evento. Orari e punto d'incontro
        // (al suo posto c'è la zona operativa), ricorrenza, posti e tipologie di
        // evento — «Fiere / Mercatini» non descrive un servizio professionale, e
        // riaprendo lo step 2 il partner ritroverebbe caselle spuntate credendo
        // di aver risposto. `max_participants` è quello che pesa davvero: il
        // publisher lo copia senza guardare il tipo, quindi restando scritto
        // farebbe esaurire un servizio professionale.
        if ($this->type === 'attivita') {
            $cleared = array_merge($cleared, array_fill_keys(
                ['time_start', 'time_end', 'meeting_point', 'recurrence', 'max_participants', 'event_categories', 'event_categories_other'],
                null,
            ));
        }

        // Verso Eventi: si abbandona il ramo professionale. La zona operativa è
        // di chi lavora su un territorio; un evento ha un punto d'incontro.
        if ($this->type === 'eventi') {
            $cleared['operating_area'] = null;
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
