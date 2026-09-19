<?php

namespace App\Livewire\Admin\People;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class Inbox extends Component
{
    public function render()
    {
        return view('livewire.admin.people.inbox')
            ->layout('layouts::admin')
            ->title('Contatti e candidature');
    }
}
