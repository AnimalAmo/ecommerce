<?php

namespace App\Livewire\Admin\Catalog;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class CatalogIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.catalog.index')
            ->layout('layouts::admin')
            ->title('Catalogo');
    }
}
