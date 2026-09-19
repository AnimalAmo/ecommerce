<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class PageIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.content.page-index')
            ->layout('layouts::admin')
            ->title('Pagine');
    }
}
