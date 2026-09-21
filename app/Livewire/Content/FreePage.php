<?php

namespace App\Livewire\Content;

use App\Models\Page\Page;
use Livewire\Component;

/**
 * Pagina libera creata dal pannello (/pagina/{slug}). Una pagina legale non
 * risponde qui: ha la sua rotta, e servirla due volte creerebbe un duplicato.
 */
class FreePage extends Component
{
    public Page $page;

    public function mount(string $slug): void
    {
        $this->page = Page::query()
            ->free()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.content.free-page')->title($this->page->titleFor());
    }
}
