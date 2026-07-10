<?php

namespace App\Livewire\Concerns;

/**
 * Fornisce agli step del wizard la lista di orari selezionabili a intervalli
 * di mezz'ora (00:00 → 23:30), da passare alla view per le tendine orario.
 */
trait ProvidesTimeSlots
{
    /** Orari selezionabili (mezz'ora): 00:00 → 23:30. */
    public function times(): array
    {
        $times = [];
        for ($h = 0; $h < 24; $h++) {
            $times[] = sprintf('%02d:00', $h);
            $times[] = sprintf('%02d:30', $h);
        }

        return $times;
    }
}
