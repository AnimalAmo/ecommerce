<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Grazie')]
class WorkWithUsThanks extends Component
{
    public function render()
    {
        return view('livewire.work-with-us-thanks');
    }
}
