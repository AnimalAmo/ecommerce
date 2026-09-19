<?php

namespace App\Livewire\Admin;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class Search extends Component
{
    public function render()
    {
        return view('livewire.admin.search')
            ->layout('layouts::admin')
            ->title('Cerca');
    }
}
