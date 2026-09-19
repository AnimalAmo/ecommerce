<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class CommunityIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.content.community-index')
            ->layout('layouts::admin')
            ->title('Community');
    }
}
