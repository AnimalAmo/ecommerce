<?php

namespace App\Livewire;

use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Attività ed Eventi')]
class Events extends Component
{
    use TogglesFavorites;

    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    public function search(): void
    {
        // TODO: filter events once the listing backend exists.
    }

    public function render()
    {
        return view('livewire.events', [
            // Le 12 card della griglia XD; le card home (position null) restano fuori.
            'events' => Event::whereNotNull('position')->orderBy('position')->get(),
        ]);
    }
}
