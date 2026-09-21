<?php

namespace App\Livewire\Admin\Content;

use App\Models\Article\Article;
use App\Services\Content\ArticleService;
use App\Support\HtmlSanitizer;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Editor di un articolo di Animal Times (design: is_articolo): testi it/en,
 * copertina nella media library, categoria, data e indirizzo.
 *
 * Tre modi di salvare, che differiscono solo per la data di pubblicazione:
 * "Salva bozza" la toglie, "Pubblica" mette quella scelta (o oggi), "Salva"
 * su un articolo già uscito tiene quella del form. La copertina è
 * obbligatoria solo per pubblicare: una bozza si salva anche senza.
 */
class ArticleEdit extends Component
{
    use WithFileUploads;

    public ?Article $article = null;

    public string $locale = 'it';

    /** @var array<string, string> */
    public array $title = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $excerpt = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $body = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $coverAlt = ['it' => '', 'en' => ''];

    /** @var TemporaryUploadedFile|null */
    public $cover = null;

    public string $category = '';

    public string $publishedAt = '';

    public string $slug = '';

    public bool $previewing = false;

    public function mount(?Article $article = null): void
    {
        $this->article = $article;

        if ($article === null) {
            return;
        }

        foreach (['it', 'en'] as $locale) {
            $this->title[$locale] = (string) $article->getTranslation('title', $locale, false);
            $this->excerpt[$locale] = (string) $article->getTranslation('excerpt', $locale, false);
            $this->body[$locale] = (string) $article->getTranslation('body', $locale, false);
            $this->coverAlt[$locale] = (string) $article->getTranslation('cover_alt', $locale, false);
        }

        $this->category = (string) $article->category;
        $this->publishedAt = (string) $article->published_at?->toDateString();
        $this->slug = $article->slug;
    }

    public function updatedLocale(): void
    {
        if (! in_array($this->locale, ['it', 'en'], true)) {
            $this->locale = 'it';
        }
    }

    public function updatedCover(): void
    {
        $this->validateOnly('cover', $this->rules(), __('admin-content.validation'), $this->attributes());
    }

    public function saveDraft(ArticleService $articles): void
    {
        $this->persist($articles, publishedAt: null, publishing: false);
    }

    /** Pubblica con la data scelta, o oggi. Una data futura lo programma. */
    public function publish(ArticleService $articles): void
    {
        $date = $this->publishedAt !== '' ? $this->publishedAt : today()->toDateString();

        $this->persist($articles, publishedAt: $date, publishing: true);
    }

    /** "Salva" su un articolo già pubblicato o programmato: resta fuori dalla bozza. */
    public function save(ArticleService $articles): void
    {
        if ($this->article?->published_at === null) {
            $this->saveDraft($articles);

            return;
        }

        $this->publish($articles);
    }

    public function unpublish(ArticleService $articles): void
    {
        if ($this->article === null) {
            return;
        }

        $articles->unpublish($this->article);
        $this->publishedAt = '';

        Flux::toast(text: __('admin-content.articles.unpublished'), variant: 'success');
    }

    public function preview(): void
    {
        $this->previewing = true;

        Flux::modal('article-preview')->show();
    }

    public function delete(ArticleService $articles): void
    {
        if ($this->article === null) {
            return;
        }

        $articles->delete($this->article);

        Flux::toast(text: __('admin-content.articles.deleted'), variant: 'success');

        $this->redirectRoute('admin.articles.index', navigate: true);
    }

    public function render()
    {
        $article = $this->article;
        $status = $article?->status();

        return view('livewire.admin.content.article-edit', [
            'status' => $status,
            'heading' => $article?->titleFor('it') ?: __('admin-content.articles.new_title'),
            'sub' => $this->subtitle(),
            'publicUrl' => $status === Article::PUBLISHED ? route('news.detail', $article->slug) : null,
            'coverPreview' => $this->coverPreview(),
            'slugPrefix' => rtrim(route('news.detail', ['article' => '_']), '_'),
            'preview' => $this->previewing ? $this->previewData() : null,
            'statusTones' => [Article::PUBLISHED => 'success', Article::SCHEDULED => 'info', Article::DRAFT => 'muted'],
        ])
            ->layout('layouts::admin')
            ->title($article?->titleFor('it') ?: __('admin-content.articles.new_title'));
    }

    private function persist(ArticleService $articles, ?string $publishedAt, bool $publishing): void
    {
        $this->resetErrorBag();

        try {
            $this->validate($this->rules(), __('admin-content.validation'), $this->attributes());
        } catch (ValidationException $exception) {
            $this->focusLocaleOf(array_keys($exception->errors()));

            throw $exception;
        }

        $wanted = Str::slug($this->slug, '-', 'it');

        if ($wanted !== '' && $articles->slugTaken($wanted, $this->article?->id)) {
            $this->addError('slug', __('admin-content.validation.unique', ['attribute' => __('admin-content.articles.fields.slug')]));

            return;
        }

        if ($publishing && ! $this->readyToPublish()) {
            return;
        }

        $creating = $this->article === null;
        $wasDraft = $this->article?->published_at === null;

        $article = $articles->save($this->article, [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'cover_alt' => $this->coverAlt,
            'category' => $this->category,
            'slug' => $this->slug,
            'published_at' => $publishedAt,
        ], Auth::user());

        if ($this->cover !== null) {
            $articles->replaceCover($article, $this->cover);
            $this->cover = null;
        }

        $status = $article->status();

        Flux::toast(variant: 'success', text: match (true) {
            $status === Article::DRAFT => __('admin-content.articles.draft_saved'),
            $status === Article::SCHEDULED => __('admin-content.articles.scheduled', ['date' => $article->published_at->locale('it')->translatedFormat('j F Y')]),
            $wasDraft => __('admin-content.articles.published'),
            default => __('admin-content.articles.saved'),
        });

        if ($creating) {
            $this->redirectRoute('admin.articles.edit', $article, navigate: true);

            return;
        }

        $this->mount($article->refresh());
    }

    /** Per andare sul sito servono il testo italiano e una copertina. */
    private function readyToPublish(): bool
    {
        if (HtmlSanitizer::clean($this->body['it']) === '') {
            $this->locale = 'it';
            $this->addError('body.it', __('admin-content.articles.body_required'));

            return false;
        }

        if ($this->cover === null && ! $this->article?->hasMedia(Article::COVER)) {
            $this->addError('cover', __('admin-content.articles.cover_required'));

            return false;
        }

        return true;
    }

    private function subtitle(): string
    {
        $article = $this->article;

        if ($article === null) {
            return __('admin-content.articles.sub_new');
        }

        $line = match ($article->status()) {
            Article::DRAFT => __('admin-content.articles.sub_draft', [
                'date' => $article->updated_at->locale('it')->translatedFormat('j M Y'),
                'time' => $article->updated_at->format('H:i'),
            ]),
            Article::SCHEDULED => __('admin-content.articles.sub_scheduled', ['date' => $article->published_at->locale('it')->translatedFormat('j M Y')]),
            default => __('admin-content.articles.sub_published', ['date' => $article->published_at->locale('it')->translatedFormat('j M Y')]),
        };

        $author = $article->author?->name;

        return $author ? $line.' · '.__('admin-content.articles.sub_author', ['name' => $author]) : $line;
    }

    /** La copertina appena scelta (non ancora salvata) o quella salvata. */
    private function coverPreview(): ?string
    {
        if ($this->cover instanceof TemporaryUploadedFile && $this->cover->isPreviewable()) {
            return $this->cover->temporaryUrl();
        }

        return $this->article?->coverUrl('card');
    }

    /** @return array{title: string, excerpt: string, html: string, cover: string|null, fallback: bool} */
    private function previewData(): array
    {
        $locale = $this->locale;
        $html = HtmlSanitizer::clean($this->body[$locale] ?? '');
        $fallback = false;

        if ($html === '' && $locale !== Article::SOURCE_LOCALE) {
            $locale = Article::SOURCE_LOCALE;
            $html = HtmlSanitizer::clean($this->body[$locale]);
            $fallback = true;
        }

        return [
            'title' => trim($this->title[$this->locale]) ?: trim($this->title[Article::SOURCE_LOCALE]),
            'excerpt' => trim($this->excerpt[$locale]),
            'html' => $html,
            'cover' => $this->coverPreview(),
            'fallback' => $fallback,
        ];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'title.it' => ['required', 'string', 'max:191'],
            'title.en' => ['nullable', 'string', 'max:191'],
            'excerpt.*' => ['nullable', 'string', 'max:500'],
            'body.*' => ['nullable', 'string', 'max:200000'],
            'coverAlt.*' => ['nullable', 'string', 'max:191'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'category' => ['nullable', Rule::in(Article::CATEGORIES)],
            'publishedAt' => ['nullable', 'date_format:Y-m-d'],
            'slug' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        $attributes = [
            'cover' => __('admin-content.articles.fields.cover'),
            'category' => __('admin-content.articles.fields.category'),
            'publishedAt' => __('admin-content.articles.fields.published_at'),
            'slug' => __('admin-content.articles.fields.slug'),
        ];

        foreach (['it', 'en'] as $locale) {
            $attributes["title.{$locale}"] = __("admin-content.articles.fields.title_{$locale}");
            $attributes["excerpt.{$locale}"] = __("admin-content.articles.fields.excerpt_{$locale}");
            $attributes["body.{$locale}"] = __("admin-content.articles.fields.body_{$locale}");
            $attributes["coverAlt.{$locale}"] = __("admin-content.articles.fields.cover_alt_{$locale}");
        }

        return $attributes;
    }

    /** @param  list<string>  $keys */
    private function focusLocaleOf(array $keys): void
    {
        foreach ($keys as $key) {
            foreach (['it', 'en'] as $locale) {
                if (str_ends_with($key, '.'.$locale)) {
                    $this->locale = $locale;

                    return;
                }
            }
        }
    }
}
