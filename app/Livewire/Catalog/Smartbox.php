<?php

namespace App\Livewire\Catalog;

use App\Livewire\Concerns\TogglesFavorites;
use App\Models\SmartboxPackage\SmartboxPackage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('AnimalAmo — Smartbox')]
class Smartbox extends Component
{
    use TogglesFavorites;
    use WithPagination;

    /** Card per pagina: la griglia XD è 4 colonne × 3 righe. */
    private const PER_PAGE = 12;

    public function render()
    {
        return view('livewire.catalog.smartbox', [
            // Ordine di griglia XD (riga per riga).
            'boxes' => SmartboxPackage::orderBy('position')->paginate(self::PER_PAGE),
        ]);
    }
}
