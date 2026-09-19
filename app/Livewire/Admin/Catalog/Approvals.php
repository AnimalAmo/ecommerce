<?php

namespace App\Livewire\Admin\Catalog;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class Approvals extends Component
{
    public function render()
    {
        return view('livewire.admin.catalog.approvals')
            ->layout('layouts::admin')
            ->title('Schede da approvare');
    }
}
