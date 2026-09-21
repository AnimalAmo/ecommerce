<?php

namespace App\Livewire\Admin\Content;

use App\Models\Page\Page;
use App\Services\Content\ContentBlockService;
use App\Services\Content\PageService;
use App\Support\HtmlSanitizer;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Editor di una pagina a database: le tre legali (slug fisso, data di
 * revisione) e le pagine libere che la cliente crea da sé (indirizzo e piede
 * del sito a scelta). Il corpo arriva da flux:editor e passa da HtmlSanitizer
 * prima del salvataggio: sul sito esce con {!! !!}.
 */
class PageEdit extends Component
{
    public ?Page $page = null;

    public string $locale = 'it';

    /** @var array<string, string> lingua => titolo */
    public array $title = ['it' => '', 'en' => ''];

    /** @var array<string, string> lingua => HTML */
    public array $body = ['it' => '', 'en' => ''];

    public string $slug = '';

    public string $footerColumn = '';

    public string $lastUpdatedAt = '';

    /** L'anteprima si calcola solo mentre la modale è aperta. */
    public bool $previewing = false;

    public function mount(?Page $page = null): void
    {
        $this->page = $page;

        if ($page === null) {
            return;
        }

        foreach (ContentBlockService::LOCALES as $locale) {
            $this->title[$locale] = (string) $page->getTranslation('title', $locale, false);
            $this->body[$locale] = (string) $page->getTranslation('body', $locale, false);
        }

        $this->slug = (string) $page->slug;
        $this->footerColumn = (string) $page->footer_column;
        $this->lastUpdatedAt = (string) $page->last_updated_at?->toDateString();
    }

    public function updatedLocale(): void
    {
        if (! in_array($this->locale, ContentBlockService::LOCALES, true)) {
            $this->locale = 'it';
        }
    }

    public function save(PageService $pages): void
    {
        $this->resetErrorBag();

        $legal = $this->isLegal();

        if (! $legal) {
            $this->slug = $pages->slug($this->slug);
        }

        try {
            $this->validate($this->rules($legal), __('admin-content.validation'), $this->attributes());
        } catch (ValidationException $exception) {
            $this->focusLocaleOf(array_keys($exception->errors()));

            throw $exception;
        }

        // "<p></p>" passa `required` ma è l'editor vuoto: sul sito sarebbe una pagina bianca.
        if (HtmlSanitizer::clean($this->body['it']) === '') {
            $this->locale = 'it';
            $this->addError('body.it', __('admin-content.pages.body_required'));

            return;
        }

        $creating = $this->page === null;

        $page = $pages->save($this->page, [
            'title' => $this->title,
            'body' => $this->body,
            'slug' => $this->slug,
            'footer_column' => $this->footerColumn,
            'last_updated_at' => $this->lastUpdatedAt,
        ], Auth::user());

        Flux::toast(text: __('admin-content.pages.saved'), variant: 'success');

        if ($creating) {
            $this->redirectRoute('admin.pages.edit', $page, navigate: true);

            return;
        }

        // Il form riparte da quello che è stato salvato davvero (HTML ripulito, slug normalizzato).
        $this->mount($page->refresh());
    }

    public function preview(): void
    {
        $this->previewing = true;

        Flux::modal('page-preview')->show();
    }

    public function delete(PageService $pages): void
    {
        if ($this->page === null || $this->isLegal()) {
            return;
        }

        $pages->delete($this->page);

        Flux::toast(text: __('admin-content.pages.deleted'), variant: 'success');

        $this->redirectRoute('admin.pages.index', navigate: true);
    }

    public function render(PageService $pages)
    {
        $legal = $this->isLegal();
        $publicUrl = $this->page?->publicUrl();
        $updated = $this->page?->updated_at;

        return view('livewire.admin.content.page-edit', [
            'legal' => $legal,
            'heading' => $this->page?->titleFor('it') ?: __('admin-content.pages.new_title'),
            'kindLabel' => __('admin-content.pages.kinds.'.($legal ? Page::KIND_LEGAL : Page::KIND_FREE)),
            'path' => $publicUrl === null ? null : (parse_url($publicUrl, PHP_URL_PATH) ?: '/'),
            'updated' => $updated
                ? __('admin-content.common.modified_on', ['date' => $updated->locale('it')->translatedFormat('j M Y')])
                : __('admin-content.pages.never_saved'),
            'publicUrl' => $publicUrl,
            'slugUrl' => $legal ? null : $pages->freePageUrl($pages->slug($this->slug)),
            'footerColumns' => Page::FOOTER_COLUMNS,
            'preview' => $this->previewing ? $this->previewData() : null,
        ])
            ->layout('layouts::admin')
            ->title($this->page?->titleFor('it') ?: __('admin-content.pages.new_title'));
    }

    /**
     * Quello che il sito mostrerebbe nella lingua aperta: HTML ripulito come al
     * salvataggio e, dove l'inglese manca, l'italiano (stessa rete del model).
     *
     * @return array{title: string, html: string, fallback: bool}
     */
    private function previewData(): array
    {
        $title = trim($this->title[$this->locale] ?? '');
        $html = HtmlSanitizer::clean($this->body[$this->locale] ?? '');
        $fallback = false;

        if ($this->locale !== Page::SOURCE_LOCALE) {
            if ($html === '') {
                $html = HtmlSanitizer::clean($this->body[Page::SOURCE_LOCALE]);
                $fallback = true;
            }

            $title = $title !== '' ? $title : trim($this->title[Page::SOURCE_LOCALE]);
        }

        return ['title' => $title, 'html' => $html, 'fallback' => $fallback];
    }

    private function isLegal(): bool
    {
        return $this->page?->isLegal() ?? false;
    }

    /** @return array<string, mixed> */
    private function rules(bool $legal): array
    {
        $rules = [
            'title.it' => ['required', 'string', 'max:191'],
            'title.en' => ['nullable', 'string', 'max:191'],
            'body.it' => ['required', 'string', 'max:200000'],
            'body.en' => ['nullable', 'string', 'max:200000'],
        ];

        if ($legal) {
            return $rules + ['lastUpdatedAt' => ['nullable', 'date']];
        }

        return $rules + [
            'slug' => ['required', 'string', 'max:120', Rule::unique('pages', 'slug')->ignore($this->page?->id)],
            'footerColumn' => ['nullable', Rule::in(Page::FOOTER_COLUMNS)],
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        return [
            'title.it' => __('admin-content.pages.fields.title_it'),
            'title.en' => __('admin-content.pages.fields.title_en'),
            'body.it' => __('admin-content.pages.fields.body_it'),
            'body.en' => __('admin-content.pages.fields.body_en'),
            'slug' => __('admin-content.pages.fields.slug'),
            'footerColumn' => __('admin-content.pages.fields.footer_column'),
            'lastUpdatedAt' => __('admin-content.pages.fields.last_updated_at'),
        ];
    }

    /**
     * I campi di una lingua stanno nella sua scheda: con l'errore nell'altra
     * lingua il salvataggio sembrerebbe non fare niente.
     *
     * @param  list<string>  $keys
     */
    private function focusLocaleOf(array $keys): void
    {
        foreach ($keys as $key) {
            foreach (ContentBlockService::LOCALES as $locale) {
                if (str_ends_with($key, '.'.$locale)) {
                    $this->locale = $locale;

                    return;
                }
            }
        }
    }
}
