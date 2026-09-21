<?php

namespace App\Livewire\Admin\Content;

use App\Models\Article\Article;
use App\Services\Content\ArticleService;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Elenco degli articoli di Animal Times (design: is_animaltimes). */
class ArticleIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 20;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    /** Articolo di cui si sta confermando l'eliminazione. */
    public ?int $deleting = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'category'], true)) {
            $this->resetPage();
        }
    }

    public function askDelete(int $id): void
    {
        $this->deleting = Article::findOrFail($id)->id;

        Flux::modal('article-delete')->show();
    }

    public function confirmDelete(ArticleService $articles): void
    {
        $article = $this->deleting === null ? null : Article::find($this->deleting);

        $this->deleting = null;
        Flux::modal('article-delete')->close();

        if ($article === null) {
            return;
        }

        $articles->delete($article);

        Flux::toast(text: __('admin-content.articles.deleted'), variant: 'success');
    }

    public function render(ArticleService $articles)
    {
        $all = $articles->list([
            'search' => $this->search,
            'status' => $this->status,
            'category' => $this->category,
        ]);

        $page = new LengthAwarePaginator(
            $all->forPage($this->getPage(), self::PER_PAGE)->values(),
            $all->count(),
            self::PER_PAGE,
            $this->getPage(),
        );

        $totals = $articles->totals();

        return view('livewire.admin.content.article-index', [
            'page' => $page,
            'sub' => __('admin-content.articles.sub', [
                'published' => trans_choice('admin-content.articles.published_count', $totals['published']),
                'drafts' => trans_choice('admin-content.articles.drafts_count', $totals['drafts']),
            ]),
            'statusTones' => [Article::PUBLISHED => 'success', Article::SCHEDULED => 'info', Article::DRAFT => 'muted'],
            'deletingTitle' => $this->deleting ? Article::find($this->deleting)?->titleFor('it') : null,
        ])
            ->layout('layouts::admin')
            ->title(__('admin-content.articles.title'));
    }
}
