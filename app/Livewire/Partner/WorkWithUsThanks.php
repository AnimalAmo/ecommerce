<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class WorkWithUsThanks extends Component
{
    public function render()
    {
        return view('livewire.partner.work-with-us-thanks')
            ->title(__('partner.title_thanks'));
    }
}
