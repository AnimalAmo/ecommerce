<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Concerns\ProvidesTimeSlots;
use Livewire\Component;

class ActivityIncluded extends Component
{
    use InteractsWithStructureDraft, ProvidesTimeSlots;

    /** Servizi presenti (multi-scelta). */
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

    /** Regole della struttura (multi-scelta). */
    public array $rules = [];

    public function mount(): void
    {
        $draft = $this->draft();
        $this->services = $draft->services ?? [];
        $this->additional = $draft->additional_services ?? [];
        $this->additionalOther = $draft->additional_other ?? '';
        $this->mealTimes = $draft->meal_times ?: $this->mealTimes;
        $this->rules = $draft->rules ?? [];
    }

    public function next(): void
    {
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string'],
            'additional' => ['array'],
            'additional.*' => ['string'],
            'additionalOther' => ['nullable', 'string', 'max:200'],
            'mealTimes.*.from' => ['nullable', 'string'],
            'mealTimes.*.to' => ['nullable', 'string'],
            'rules' => ['array'],
            'rules.*' => ['string'],
        ]);

        $this->saveStep([
            'services' => $this->services,
            'additional_services' => $this->additional,
            'additional_other' => $this->additionalOther,
            'meal_times' => $this->mealTimes,
            'rules' => $this->rules,
        ], 6);

        $this->redirectRoute('partner.activity.animal-services');
    }

    public function render()
    {
        return view('livewire.partner.activity.activity-included', ['times' => $this->times()])
            ->title(__('partner.activity_included.title'));
    }
}
