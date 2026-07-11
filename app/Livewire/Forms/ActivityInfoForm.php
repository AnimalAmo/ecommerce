<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Attività/Eventi — step 5 "Informazioni generali". Le Attività richiedono solo
 * le date; gli Eventi anche gli orari di inizio/fine (guidato da `$isEvent`).
 */
class ActivityInfoForm extends Form
{
    public string $dateStart = '';

    public string $dateEnd = '';

    /** Orari: solo per gli Eventi. */
    public string $timeStart = '';

    public string $timeEnd = '';

    /** Gli Eventi hanno anche ora inizio/fine; le Attività solo le date. Flag di controllo, non persistito. */
    public bool $isEvent = false;

    public function rules(): array
    {
        $rules = [
            'dateStart' => ['required', 'date'],
            'dateEnd' => ['required', 'date', 'after_or_equal:dateStart'],
        ];

        if ($this->isEvent) {
            // H:i: il publisher compone i datetime con explode(':') — il select
            // offre solo slot validi ma la property è client-settable.
            $rules['timeStart'] = ['required', 'date_format:H:i'];
            $rules['timeEnd'] = ['required', 'date_format:H:i'];
        }

        return $rules;
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->dateStart = $draft->date_start ? $draft->date_start->format('Y-m-d') : '';
        $this->dateEnd = $draft->date_end ? $draft->date_end->format('Y-m-d') : '';
        $this->timeStart = $draft->time_start ?? '';
        $this->timeEnd = $draft->time_end ?? '';
        $this->isEvent = $draft->type === 'eventi';
    }

    /** Attributi nel formato colonne della bozza; gli orari solo per gli Eventi. */
    public function toDraft(): array
    {
        $attributes = ['date_start' => $this->dateStart, 'date_end' => $this->dateEnd];

        if ($this->isEvent) {
            $attributes['time_start'] = $this->timeStart;
            $attributes['time_end'] = $this->timeEnd;
        }

        return $attributes;
    }
}
