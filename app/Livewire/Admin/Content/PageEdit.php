<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class PageEdit extends Component
{
    public function render()
    {
        return view('livewire.admin.content.page-edit')
            ->layout('layouts::admin')
            ->title('Pagina');
    }
}
