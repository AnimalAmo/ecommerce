<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Attività/Eventi — step 5 "Informazioni generali". Un Evento vuole data E
 * orari; un'Attività non vuole gli orari e la data ce l'ha facoltativa
 * (risposta della cliente, 27/09/2026: un dog sitter o un toelettatore non ha
 * una data). Tutto guidato da `$isEvent`.
 */
class ActivityInfoForm extends Form
{
    public string $dateStart = '';

    public string $dateEnd = '';

    /** Orari: solo per gli Eventi. */
    public string $timeStart = '';

    public string $timeEnd = '';

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
     */
    public function rules(): array
    {
        if ($this->isEvent) {
            return [
                'dateStart' => ['required', 'date'],
                'dateEnd' => ['required', 'date', 'after_or_equal:dateStart'],
                // H:i: il publisher compone i datetime con explode(':') — il select
                // offre solo slot validi ma la property è client-settable.
                'timeStart' => ['required', 'date_format:H:i'],
                'timeEnd' => ['required', 'date_format:H:i'],
            ];
        }

        return [
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
        $this->isEvent = $draft->type === 'eventi';
    }

    /**
     * Attributi nel formato colonne della bozza; gli orari solo per gli Eventi.
     *
     * Le date vanno a NULL esplicito quando sono vuote: `date_start`/`date_end`
     * sono castate `date`, e `''` non diventa NULL ma OGGI (`Carbon::parse('')`
     * torna adesso). Un'attività salvata senza data si ritroverebbe con la data
     * del giorno in cui è stata compilata, e il gate di pubblicazione la
     * lascerebbe passare credendo che la data ci sia. Gli orari se la cavano da
     * soli perché per le attività la chiave non viene nemmeno scritta, ma qui
     * scriverla serve: una data cancellata deve tornare NULL a database.
     */
    public function toDraft(): array
    {
        $attributes = [
            'date_start' => blank($this->dateStart) ? null : $this->dateStart,
            'date_end' => blank($this->dateEnd) ? null : $this->dateEnd,
        ];

        if ($this->isEvent) {
            $attributes['time_start'] = $this->timeStart;
            $attributes['time_end'] = $this->timeEnd;
        }

        return $attributes;
    }
}
