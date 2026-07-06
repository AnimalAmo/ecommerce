<?php

namespace App\Livewire;

use App\Livewire\Concerns\TogglesFavorites;
use App\Models\Event\Event;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('AnimalAmo — Attività ed Eventi')]
class Events extends Component
{
    use TogglesFavorites;
    use WithPagination;

    /** Card per pagina: la griglia XD è 4 colonne × 3 righe. */
    private const PER_PAGE = 12;

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
            // Pagina 1 = le 12 card della griglia XD (position); a seguire, paginati,
            // gli eventi finora solo in home (position null, ordinati per home_position).
            'events' => Event::orderByRaw('position is null')
                ->orderBy('position')
                ->orderBy('home_position')
                ->paginate(self::PER_PAGE),
        ]);
    }
}
