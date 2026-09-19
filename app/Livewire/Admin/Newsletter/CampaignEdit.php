<?php

namespace App\Livewire\Admin\Newsletter;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class CampaignEdit extends Component
{
    public function render()
    {
        return view('livewire.admin.newsletter.campaign-edit')
            ->layout('layouts::admin')
            ->title('Scrivi una newsletter');
    }
}
