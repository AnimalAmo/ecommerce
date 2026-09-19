<?php

namespace App\Livewire\Admin\Newsletter;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class NewsletterIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.newsletter.index')
            ->layout('layouts::admin')
            ->title('Newsletter');
    }
}
