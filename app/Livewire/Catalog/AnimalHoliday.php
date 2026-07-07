<?php

namespace App\Livewire\Catalog;

use App\Models\Region\Region;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('AnimalAmo — Animal Holiday')]
class AnimalHoliday extends Component
{
    /** Filtro "Dove": deep-linkabile (?dove=…); la hero home ci reindirizza con ?dove. */
    #[Url(as: 'dove')]
    public string $where = '';

    public string $when = '';

    public string $guests = '';

    public string $animals = '';

    public function search(): void
    {
        // La ricerca "Dove" filtra le regioni in render(): nessuna paginazione qui.
        // Il "Quando"/ospiti/animali non filtrano ancora (step 6 disponibilità).
    }

    public function render()
    {
        $term = trim($this->where);

        return view('livewire.catalog.animal-holiday', [
            // Ordine di griglia XD (riga per riga), non alfabetico. "Dove" restringe per nome
            // regione (LIKE %dove%, case-insensitive). Zero risultati → messaggio nel template
            // ("Nessuna località trovata"; il fallback con alternative simili arriva allo step 6).
            'regions' => Region::query()
                ->when($term !== '', fn ($query) => $query->whereLike('name', '%'.addcslashes($term, '\%_').'%'))
                ->orderBy('position')
                ->get(),
        ]);
    }
}
