<?php

namespace App\Livewire\Admin;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class Home extends Component
{
    public function render()
    {
        return view('livewire.admin.home')
            ->layout('layouts::admin')
            ->title('Home pannello');
    }
}
