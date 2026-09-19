<?php

namespace App\Livewire\Admin\Catalog;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class CatalogShow extends Component
{
    public function render()
    {
        return view('livewire.admin.catalog.show')
            ->layout('layouts::admin')
            ->title('Scheda');
    }
}
