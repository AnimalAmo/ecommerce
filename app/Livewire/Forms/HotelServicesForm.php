<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Struttura (hotel) — step 7 "Servizi". Servizi struttura + aggiuntivi (con
 * orari pasti) + regole della struttura.
 *
 * NB: il campo "regole" è esposto come `structureRules` per non collidere con il
 * metodo `rules()` di Livewire\Form; su bozza resta la colonna `rules`.
 */
class HotelServicesForm extends Form
{
    /** Servizi struttura selezionati (multi-scelta). */
    public array $services = [];

    /** Servizi aggiuntivi presenti (multi-scelta). */
    public array $additional = [];

    /** Dettaglio per "Altro" servizio aggiuntivo. */
    public string $additionalOther = '';

    /** Orari (inizio/fine) per i pasti: colazione | pranzo | cena. */
    public array $mealTimes = [
        'colazione' => ['from' => '', 'to' => ''],
        'pranzo' => ['from' => '', 'to' => ''],
        'cena' => ['from' => '', 'to' => ''],
    ];

    /** Regole della struttura (multi-scelta). Colonna bozza: `rules`. */
    public array $structureRules = [];

    public function rules(): array
    {
        return [
            'services' => ['array'],
            'services.*' => ['string'],
            'additional' => ['array'],
            'additional.*' => ['string'],
            'additionalOther' => ['nullable', 'string', 'max:200'],
            'mealTimes.*.from' => ['nullable', 'string'],
            'mealTimes.*.to' => ['nullable', 'string'],
            'structureRules' => ['array'],
            'structureRules.*' => ['string'],
        ];
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->services = $draft->services ?? [];
        $this->additional = $draft->additional_services ?? [];
        $this->additionalOther = $draft->additional_other ?? '';
        $this->mealTimes = $draft->meal_times ?: $this->mealTimes;
        $this->structureRules = $draft->rules ?? [];
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'services' => $this->services,
            'additional_services' => $this->additional,
            'additional_other' => $this->additionalOther,
            'meal_times' => $this->mealTimes,
            'rules' => $this->structureRules,
        ];
    }
}
