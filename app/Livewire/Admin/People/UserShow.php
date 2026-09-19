<?php

namespace App\Livewire\Admin\People;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class UserShow extends Component
{
    public function render()
    {
        return view('livewire.admin.people.user-show')
            ->layout('layouts::admin')
            ->title('Iscritto');
    }
}
