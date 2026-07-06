<?php

namespace App\Livewire;

use App\Models\SmartboxPackage\SmartboxPackage;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Smartbox')]
class Smartbox extends Component
{
    public function render()
    {
        return view('livewire.smartbox', [
            // Ordine di griglia XD (riga per riga).
            'boxes' => SmartboxPackage::orderBy('position')->get(),
        ]);
    }
}
