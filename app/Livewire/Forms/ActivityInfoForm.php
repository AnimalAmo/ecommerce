<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Attività/Eventi — step 5 "Informazioni generali". Un Evento vuole data E
 * orari; un'Attività non vuole gli orari e la data ce l'ha facoltativa
 * (risposta della cliente, 27/09/2026: un dog sitter o un toelettatore non ha
 * una data). Tutto guidato da `$isEvent`.
 *
 * Qui stanno anche i tre campi aggiunti dalle risposte della cliente del
 * 27/09/2026: la prenotazione (entrambi i rami), la ricorrenza e i posti
 * disponibili (solo eventi).
 */
class ActivityInfoForm extends Form
{
    public string $dateStart = '';

    public string $dateEnd = '';

    /** Orari: solo per gli Eventi. */
    public string $timeStart = '';

    public string $timeEnd = '';

    /**
     * Prenotazione (gruppo `booking_requirement`): facoltativa e per entrambi i
     * rami. La cliente la chiede come «possibilità di prenotazione» ai
     * professionisti e «obbligatoria o facoltativa» agli eventi: è la stessa
     * informazione. Resta un'etichetta per la scheda, non tocca il funnel
     * d'acquisto.
     */
    public string $bookingRequirement = '';

    /**
     * Ricorrenza (gruppo `event_recurrence`): solo eventi, facoltativa e SOLA
     * ETICHETTA. La cliente ha escluso la generazione automatica delle date
     * ripetute, quindi 'ricorrente' non deve far nascere nessuna logica di
     * ripetizione: è un'informazione che il cliente legge sulla scheda.
     */
    public string $recurrence = '';

    /**
     * Posti disponibili: solo eventi, facoltativo. «Numero effettivo, con
     * blocco delle iscrizioni al raggiungimento del limite» (cliente,
     * 27/09/2026); vuoto = nessun limite.
     *
     * Stringa e non `?int`: un input svuotato manda `''`, che un int property
     * trasformerebbe in 0 — cioè in un evento esaurito prima di aprire.
     */
    public string $maxParticipants = '';

    /** Gli Eventi hanno ora inizio/fine e data obbligatoria; le Attività nessuno dei due. Flag di controllo, non persistito. */
    public bool $isEvent = false;

    /**
     * Due rami interi invece di un array comune ritoccato: fra Evento e Attività
     * non cambia una regola, cambia l'obbligatorietà dell'intero blocco
     * data/ora (risposta della cliente, 27/09/2026). Scritti così i due
     * contratti si leggono uno sotto l'altro e nessuno dei due si porta addosso
     * un `required` dell'altro, che è esattamente l'errore da cui veniamo: la
     * data obbligatoria per tutti rendeva impubblicabile ogni servizio
     * professionale.
     *
     * I campi facoltativi arrivano come `''` e non come `null` (Livewire non
     * passa da ConvertEmptyStringsToNull): va bene, perché Laravel salta le
     * regole non implicite su una stringa vuota, quindi `Rule::in` e `integer`
     * non la vedono nemmeno.
     */
    public function rules(): array
    {
        $rules = [
            'bookingRequirement' => ['nullable', 'string', Rule::in(ServiceOptionLabels::slugs('booking_requirement'))],
        ];

        if ($this->isEvent) {
            return [
                ...$rules,
                'dateStart' => ['required', 'date'],
                'dateEnd' => ['required', 'date', 'after_or_equal:dateStart'],
                // H:i: il publisher compone i datetime con explode(':') — il select
                // offre solo slot validi ma la property è client-settable.
                'timeStart' => ['required', 'date_format:H:i'],
                'timeEnd' => ['required', 'date_format:H:i'],
                'recurrence' => ['nullable', 'string', Rule::in(ServiceOptionLabels::slugs('event_recurrence'))],
                // max:65535 non è pedanteria: la colonna è unsignedSmallInteger su
                // bozza ed evento, e in MySQL strict un valore più grande è un
                // errore SQL, non una validazione.
                'maxParticipants' => ['nullable', 'integer', 'min:1', 'max:65535'],
            ];
        }

        return [
            ...$rules,
            // `required_with:dateEnd`: la data d'inizio è facoltativa, ma una
            // data di fine da sola non è un dato, è una riga rotta — il
            // publisher ne farebbe un `ends_at` senza `starts_at`. Prima il
            // caso non esisteva, perché erano obbligatorie entrambe.
            'dateStart' => ['nullable', 'date', 'required_with:dateEnd'],
            'dateEnd' => ['nullable', 'date', 'after_or_equal:dateStart'],
        ];
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->dateStart = $draft->date_start ? $draft->date_start->format('Y-m-d') : '';
        $this->dateEnd = $draft->date_end ? $draft->date_end->format('Y-m-d') : '';
        $this->timeStart = $draft->time_start ?? '';
        $this->timeEnd = $draft->time_end ?? '';
        $this->bookingRequirement = $draft->booking_requirement ?? '';
        $this->recurrence = $draft->recurrence ?? '';
        $this->maxParticipants = $draft->max_participants === null ? '' : (string) $draft->max_participants;
        $this->isEvent = $draft->type === 'eventi';
    }

    /**
     * Attributi nel formato colonne della bozza; gli orari, la ricorrenza e i
     * posti solo per gli Eventi.
     *
     * Le date vanno a NULL esplicito quando sono vuote: `date_start`/`date_end`
     * sono castate `date`, e `''` non diventa NULL ma OGGI (`Carbon::parse('')`
     * torna adesso). Un'attività salvata senza data si ritroverebbe con la data
     * del giorno in cui è stata compilata, e il gate di pubblicazione la
     * lascerebbe passare credendo che la data ci sia. Gli orari se la cavano da
     * soli perché per le attività la chiave non viene nemmeno scritta, ma qui
     * scriverla serve: una data cancellata deve tornare NULL a database.
     *
     * Stessa ragione per i posti: `max_participants` è castata `integer`, e `''`
     * diventerebbe 0 — un evento esaurito senza che nessuno si sia iscritto.
     */
    public function toDraft(): array
    {
        $attributes = [
            'date_start' => blank($this->dateStart) ? null : $this->dateStart,
            'date_end' => blank($this->dateEnd) ? null : $this->dateEnd,
            'booking_requirement' => blank($this->bookingRequirement) ? null : $this->bookingRequirement,
        ];

        if ($this->isEvent) {
            $attributes['time_start'] = $this->timeStart;
            $attributes['time_end'] = $this->timeEnd;
            $attributes['recurrence'] = blank($this->recurrence) ? null : $this->recurrence;
            $attributes['max_participants'] = blank($this->maxParticipants) ? null : (int) $this->maxParticipants;
        }

        return $attributes;
    }

    /**
     * Le voci dei due select, dalla stessa mappa che li valida.
     *
     * Stanno sul Form e non nel `render()` del componente perché ActivityInfo è
     * un adattatore sottile che passa solo `times()`: mettere qui le liste tiene
     * insieme, in un file, l'elenco mostrato e l'elenco accettato.
     *
     * @return array<string, string>
     */
    public function bookingOptions(): array
    {
        return ServiceOptionLabels::options('booking_requirement');
    }

    /** @return array<string, string> */
    public function recurrenceOptions(): array
    {
        return ServiceOptionLabels::options('event_recurrence');
    }
}
