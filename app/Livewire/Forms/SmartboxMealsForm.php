<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Smartbox — step 6 "Pasti". Pasti offerti ("Nessuno" mutuamente esclusivo,
 * gestito dal componente), orari per pasto e restrizioni dietetiche.
 */
class SmartboxMealsForm extends Form
{
    /** Pasti offerti: nessuno | colazione | pranzo | cena. */
    public array $meals = [];

    /** Orari (inizio/fine) per i pasti: colazione | pranzo | cena. */
    public array $mealTimes = [
        'colazione' => ['from' => '', 'to' => ''],
        'pranzo' => ['from' => '', 'to' => ''],
        'cena' => ['from' => '', 'to' => ''],
    ];

    /** Restrizioni dietetiche soddisfatte (multi-scelta). */
    public array $dietary = [];

    public function rules(): array
    {
        return [
            'meals' => ['array'],
            'meals.*' => ['string', 'in:nessuno,colazione,pranzo,cena'],
            'mealTimes.*.from' => ['nullable', 'string'],
            'mealTimes.*.to' => ['nullable', 'string'],
            'dietary' => ['array'],
            'dietary.*' => ['string'],
        ];
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->meals = $draft->meals ?? [];
        $this->mealTimes = $draft->meal_times ?: $this->mealTimes;
        $this->dietary = $draft->dietary_restrictions ?? [];
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'meals' => $this->meals,
            'meal_times' => $this->mealTimes,
            'dietary_restrictions' => $this->dietary,
        ];
    }
}
