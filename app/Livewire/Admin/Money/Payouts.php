<?php

namespace App\Livewire\Admin\Money;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class Payouts extends Component
{
    public function render()
    {
        return view('livewire.admin.money.payouts')
            ->layout('layouts::admin')
            ->title('Incassi');
    }
}
