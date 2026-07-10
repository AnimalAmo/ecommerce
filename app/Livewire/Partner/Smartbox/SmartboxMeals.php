<?php

namespace App\Livewire\Partner\Smartbox;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Livewire\Concerns\ProvidesTimeSlots;
use App\Livewire\Forms\SmartboxMealsForm;
use Livewire\Component;

class SmartboxMeals extends Component
{
    use InteractsWithStructureDraft, ProvidesTimeSlots;

    public SmartboxMealsForm $form;

    /** Snapshot dello stato precedente dei pasti per gestire l'esclusività di "Nessuno". */
    public array $mealsPrev = [];

    public function mount(): void
    {
        $this->form->setFromDraft($this->draft());
        $this->mealsPrev = $this->form->meals;
    }

    /** "Nessuno" esclude i pasti e viceversa. */
    public function updatedFormMeals(): void
    {
        $added = array_values(array_diff($this->form->meals, $this->mealsPrev));

        if (in_array('nessuno', $added, true)) {
            $this->form->meals = ['nessuno'];
        } elseif ($added !== []) {
            $this->form->meals = array_values(array_diff($this->form->meals, ['nessuno']));
        }

        $this->mealsPrev = $this->form->meals;
    }

    public function next(): void
    {
        $this->form->validate();
        $this->saveStep($this->form->toDraft(), 6);
        $this->redirectRoute('partner.smartbox.offers');
    }

    public function render()
    {
        return view('livewire.partner.smartbox.smartbox-meals', ['times' => $this->times()])
            ->title(__('partner.smartbox_meals.title'));
    }
}
