<?php

namespace App\Livewire\Admin\Content;

use Livewire\Component;

/** Stub: modulo in costruzione. */
class ArticleEdit extends Component
{
    public function render()
    {
        return view('livewire.admin.content.article-edit')
            ->layout('layouts::admin')
            ->title('Articolo');
    }
}
