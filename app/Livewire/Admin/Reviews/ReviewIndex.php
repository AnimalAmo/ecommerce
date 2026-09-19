<?php

namespace App\Livewire\Admin\Reviews;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class ReviewIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.reviews.index')
            ->layout('layouts::admin')
            ->title('Recensioni');
    }
}
