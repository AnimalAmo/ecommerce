<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class FaqIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.content.faq-index')
            ->layout('layouts::admin')
            ->title('Domande frequenti');
    }
}
