<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Concerns\ProvidesTimeSlots;
use Livewire\Component;

class SmartboxMeals extends Component
{
    use InteractsWithStructureDraft, ProvidesTimeSlots;

    /** Pasti offerti: nessuno | colazione | pranzo | cena ("Nessuno" è mutuamente esclusivo). */
    public array $meals = [];

    /** Snapshot dello stato precedente di $meals per gestire l'esclusività di "Nessuno". */
    public array $mealsPrev = [];

    /** Orari (inizio/fine) per i pasti: colazione | pranzo | cena. */
    public array $mealTimes = [
        'colazione' => ['from' => '', 'to' => ''],
        'pranzo' => ['from' => '', 'to' => ''],
        'cena' => ['from' => '', 'to' => ''],
    ];

    /** Restrizioni dietetiche soddisfatte (multi-scelta). */
    public array $dietary = [];

    /** Pasti che, se selezionati, rivelano orari + restrizioni dietetiche. */
    public const MEAL_KEYS = ['colazione', 'pranzo', 'cena'];

    public function mount(): void
    {
        $draft = $this->draft();
        $this->meals = $draft->meals ?? [];
        $this->mealsPrev = $this->meals;
        $this->mealTimes = $draft->meal_times ?: $this->mealTimes;
        $this->dietary = $draft->dietary_restrictions ?? [];
    }

    /** "Nessuno" esclude i pasti e viceversa. */
    public function updatedMeals(): void
    {
        $added = array_values(array_diff($this->meals, $this->mealsPrev));

        if (in_array('nessuno', $added, true)) {
            $this->meals = ['nessuno'];
        } elseif ($added !== []) {
            $this->meals = array_values(array_diff($this->meals, ['nessuno']));
        }

        $this->mealsPrev = $this->meals;
    }

    public function next(): void
    {
        $this->validate([
            'meals' => ['array'],
            'meals.*' => ['string', 'in:nessuno,colazione,pranzo,cena'],
            'mealTimes.*.from' => ['nullable', 'string'],
            'mealTimes.*.to' => ['nullable', 'string'],
            'dietary' => ['array'],
            'dietary.*' => ['string'],
        ]);

        $this->saveStep([
            'meals' => $this->meals,
            'meal_times' => $this->mealTimes,
            'dietary_restrictions' => $this->dietary,
        ], 6);
        $this->redirectRoute('partner.smartbox.offers');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-meals', ['times' => $this->times()])
            ->title(__('partner.smartbox_meals.title'));
    }
}
