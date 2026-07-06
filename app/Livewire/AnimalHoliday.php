<?php

namespace App\Livewire;

use App\Models\Region\Region;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Animal Holiday')]
class AnimalHoliday extends Component
{
    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    public function search(): void
    {
        // TODO: filter structures once the listing backend exists.
    }

    public function render()
    {
        return view('livewire.animal-holiday', [
            // Ordine di griglia XD (riga per riga), non alfabetico.
            'regions' => Region::orderBy('position')->get(),
        ]);
    }
}
