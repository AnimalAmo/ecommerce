<?php

namespace App\Livewire\Admin\People;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class UserIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.people.user-index')
            ->layout('layouts::admin')
            ->title('Iscritti');
    }
}
