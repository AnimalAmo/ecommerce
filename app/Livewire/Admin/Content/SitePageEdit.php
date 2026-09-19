<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class SitePageEdit extends Component
{
    public function render()
    {
        return view('livewire.admin.content.site-page-edit')
            ->layout('layouts::admin')
            ->title('Pagina del sito');
    }
}
