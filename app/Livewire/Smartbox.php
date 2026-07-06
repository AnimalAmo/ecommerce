<?php

namespace App\Livewire;

use App\Livewire\Concerns\TogglesFavorites;
use App\Models\SmartboxPackage\SmartboxPackage;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Smartbox')]
class Smartbox extends Component
{
    use TogglesFavorites;

    public function render()
    {
        return view('livewire.smartbox', [
            // Ordine di griglia XD (riga per riga).
            'boxes' => SmartboxPackage::orderBy('position')->get(),
        ]);
    }
}
