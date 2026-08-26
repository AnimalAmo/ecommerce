<?php

namespace App\Livewire\Content;

use App\Models\Page\Page;
use Livewire\Component;

/**
 * Pagina legale servita da DB. Un solo componente per entrambe: lo slug arriva
 * dai defaults() della rotta, così aggiungere una pagina costa una riga in
 * web.php più il contenuto, non una classe nuova.
 */
class LegalPage extends Component
{
    public Page $page;

    public function mount(string $slug): void
    {
        $this->page = Page::where('slug', $slug)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.content.legal-page')->title($this->page->titleFor());
    }
}
