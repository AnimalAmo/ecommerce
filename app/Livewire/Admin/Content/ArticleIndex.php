<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class ArticleIndex extends Component
{
    public function render()
    {
        return view('livewire.admin.content.article-index')
            ->layout('layouts::admin')
            ->title('Animal Times');
    }
}
