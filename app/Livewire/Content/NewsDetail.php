<?php

namespace App\Livewire\Content;

use App\Models\Article\Article;
use Livewire\Component;

class NewsDetail extends Component
{
    /** Slug articolo dalla rotta; il nome differisce dal parametro {article} per non collidere col binding Livewire. */
    public string $articleSlug = '';

    /** Card correlate: quattro come nell'artboard XD. */
    private const RELATED = 4;

    public function mount(string $article): void
    {
        $id = Article::published()->where('slug', $article)->value('id');

        abort_if($id === null, 404);

        $this->articleSlug = $article;

        // "Letture" nel pannello: una per apertura della pagina (mount non
        // rigira sulle richieste Livewire). Dal query builder, così updated_at
        // resta la data dell'ultima modifica vera.
        Article::query()->whereKey($id)->toBase()->increment('views');
    }

    public function render()
    {
        $article = Article::published()->where('slug', $this->articleSlug)->sole();

        $related = Article::published()
            ->with('media')
            ->whereKeyNot($article->getKey())
            ->take(self::RELATED)
            ->get();

        return view('livewire.content.news-detail', [
            'article' => $article,
            'related' => $related,
        ])->title(__('news.detail_page_title', ['title' => $article->titleFor()]));
    }
}
