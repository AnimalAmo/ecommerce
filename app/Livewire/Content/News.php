<?php

namespace App\Livewire\Content;

use App\Models\Article\Article;
use Livewire\Component;

class News extends Component
{
    /** Due righe da 3 card, come la griglia dell'XD. */
    public const PER_PAGE = 6;

    public int $perPage = self::PER_PAGE;

    public function loadMore(): void
    {
        $this->perPage += self::PER_PAGE;
    }

    public function render()
    {
        $articles = Article::published()->take($this->perPage)->get();

        return view('livewire.content.news', [
            'articles' => $articles,
            // Il bottone "Carica altro" compare solo se ha qualcosa da caricare:
            // con i 4 articoli di oggi resterebbe un bottone morto in pagina.
            'hasMore' => Article::published()->count() > $articles->count(),
        ])->title(__('news.page_title'));
    }
}
