<?php

namespace App\Livewire\Partner\Registration;

use Livewire\Component;

class WorkWithUsThanks extends Component
{
    public function render()
    {
        return view('livewire.partner.registration.work-with-us-thanks')
            ->title(__('partner.title_thanks'));
    }
}
