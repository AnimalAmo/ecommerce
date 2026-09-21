<?php

namespace App\Services\Content;

use App\Models\Page\Page;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Le pagine del pannello: l'elenco unico (sezioni del sito, pagine legali,
 * pagine libere), il salvataggio delle pagine a database e i link del piede.
 */
class PageService
{
    public const FOOTER_CACHE_KEY = 'pages.footer_links';

    /** Stato di una lingua: testo di partenza, riscritto dal pannello, assente. */
    public const ORIGINAL = 'original';

    public const REWRITTEN = 'rewritten';

    public const MISSING = 'missing';

    public const KIND_SITE = 'site';

    public function __construct(private readonly ContentBlockService $blocks) {}

    /**
     * Una riga per pagina, già pronta per la tabella: prima le sezioni del
     * sito, poi le legali, poi le libere.
     *
     * @return Collection<int, array{key: string, name: string, kind: string, url: string|null, path: string, edit_url: string, locales: array<string, string>, updated_at: CarbonInterface|null}>
     */
    public function rows(): Collection
    {
        $rows = collect();

        foreach ($this->blocks->sections() as $key => $section) {
            $status = $this->blocks->sectionStatus($key);
            $url = route($section['route']);

            $rows->push([
                'key' => 'site-'.$key,
                'name' => $this->blocks->sectionLabel($key),
                'kind' => self::KIND_SITE,
                'url' => $url,
                'path' => $this->path($url),
                'edit_url' => route('admin.pages.site', $key),
                'locales' => array_map(fn (bool $rewritten): string => $rewritten ? self::REWRITTEN : self::ORIGINAL, $status['locales']),
                'updated_at' => $status['updated_at'],
            ]);
        }

        $pages = Page::query()->orderByRaw("case when kind = 'legal' then 0 else 1 end")->orderBy('id')->get();

        foreach ($pages as $page) {
            $url = $page->publicUrl();
            $locales = [];

            foreach (ContentBlockService::LOCALES as $locale) {
                $locales[$locale] = $this->localeState($page, $locale);
            }

            $untouched = ! in_array(self::REWRITTEN, $locales, true);

            $rows->push([
                'key' => 'page-'.$page->id,
                'name' => $page->titleFor('it'),
                'kind' => $page->kind,
                'url' => $url,
                'path' => $url === null ? '—' : $this->path($url),
                'edit_url' => route('admin.pages.edit', $page),
                'locales' => $locales,
                // Una legale mai toccata porta la data della semina, che non è una modifica.
                'updated_at' => $page->isLegal() && $untouched ? null : $page->updated_at,
            ]);
        }

        return $rows;
    }

    /**
     * Filtri dell'elenco. "Testo originale" vuol dire nessuna lingua riscritta;
     * "traduzione mancante" almeno una lingua senza testo.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(Collection $rows, string $search = '', string $kind = '', string $state = ''): Collection
    {
        $needle = mb_strtolower(trim($search));

        return $rows
            ->when($needle !== '', fn (Collection $rows) => $rows->filter(
                fn (array $row): bool => str_contains(mb_strtolower($row['name']), $needle)
                    || str_contains(mb_strtolower($row['path']), $needle),
            ))
            ->when($kind !== '', fn (Collection $rows) => $rows->where('kind', $kind))
            ->when($state !== '', fn (Collection $rows) => $rows->filter(fn (array $row): bool => match ($state) {
                self::REWRITTEN => in_array(self::REWRITTEN, $row['locales'], true),
                self::ORIGINAL => ! in_array(self::REWRITTEN, $row['locales'], true),
                self::MISSING => in_array(self::MISSING, $row['locales'], true),
                default => true,
            }))
            ->values();
    }

    /**
     * "Testo originale" per una legale vuol dire: il corpo è ancora quello
     * del documento versionato in database/seeders/content/. Le libere non
     * hanno un originale: o c'è il testo o manca.
     */
    public function localeState(Page $page, string $locale): string
    {
        $body = trim((string) $page->getTranslation('body', $locale, false));

        if ($body === '') {
            return self::MISSING;
        }

        if ($page->isLegal()) {
            $path = database_path("seeders/content/{$page->slug}.{$locale}.html");

            if (is_file($path) && trim((string) file_get_contents($path)) === $body) {
                return self::ORIGINAL;
            }
        }

        return self::REWRITTEN;
    }

    /**
     * Crea o aggiorna una pagina. Le legali tengono slug e tipo, e hanno la
     * data di revisione; le nuove pagine sono sempre libere (una legale nuova
     * non avrebbe una rotta) e possono comparire nel piede del sito.
     *
     * @param  array{title: array<string, string|null>, body: array<string, string|null>, slug?: string|null, footer_column?: string|null, last_updated_at?: string|null}  $data
     */
    public function save(?Page $page, array $data, ?User $editor = null): Page
    {
        $page ??= new Page(['kind' => Page::KIND_FREE]);

        foreach (ContentBlockService::LOCALES as $locale) {
            $title = trim((string) ($data['title'][$locale] ?? ''));
            $body = HtmlSanitizer::clean($data['body'][$locale] ?? '');

            $title === '' ? $page->forgetTranslation('title', $locale) : $page->setTranslation('title', $locale, $title);
            $body === '' ? $page->forgetTranslation('body', $locale) : $page->setTranslation('body', $locale, $body);
        }

        // title/body sono colonne json NOT NULL: una lingua tolta non deve lasciarle null.
        foreach (['title', 'body'] as $attribute) {
            if ($page->getTranslations($attribute) === []) {
                $page->setAttribute($attribute, []);
            }
        }

        if ($page->isLegal()) {
            $page->last_updated_at = filled($data['last_updated_at'] ?? null) ? Carbon::parse($data['last_updated_at']) : null;
        } else {
            $column = (string) ($data['footer_column'] ?? '');
            $page->footer_column = in_array($column, Page::FOOTER_COLUMNS, true) ? $column : null;
            $page->slug = $this->slug((string) ($data['slug'] ?? ''));
            $page->is_published = true;
        }

        $page->save();

        return $page;
    }

    public function delete(Page $page): void
    {
        if ($page->isLegal()) {
            throw new InvalidArgumentException(__('admin-content.pages.legal_not_deletable'));
        }

        $page->delete();
    }

    /** Lo slug come verrà salvato: minuscolo, trattini, niente barre. */
    public function slug(string $value): string
    {
        return Str::slug(Str::afterLast(trim($value, " /\t\n"), '/'), '-', 'it');
    }

    /** Indirizzo pubblico di una pagina libera, per l'anteprima dello slug nell'editor. */
    public function freePageUrl(string $slug): string
    {
        return route('page', ['slug' => $slug === '' ? '…' : $slug]);
    }

    /**
     * Link del piede del sito, per colonna. In cache (il piede è su quasi
     * ogni pagina); l'URL si calcola a ogni render perché dipende dalla lingua.
     *
     * @return array<string, list<array{title: string, url: string}>>
     */
    public function footerLinks(): array
    {
        $pages = Cache::rememberForever(self::FOOTER_CACHE_KEY, fn (): array => Page::query()
            ->free()
            ->whereNotNull('footer_column')
            ->where('is_published', true)
            ->orderBy('id')
            ->get(['id', 'slug', 'kind', 'title', 'footer_column'])
            ->map(fn (Page $page): array => [
                'slug' => $page->slug,
                'column' => $page->footer_column,
                'title' => $page->getTranslations('title'),
            ])
            ->all());

        $links = [];

        foreach ($pages as $entry) {
            $page = (new Page)->forceFill(['slug' => $entry['slug'], 'kind' => Page::KIND_FREE]);
            $page->setTranslations('title', $entry['title']);

            $links[$entry['column']][] = ['title' => $page->titleFor(), 'url' => (string) $page->publicUrl()];
        }

        return $links;
    }

    public static function forgetFooterLinks(): void
    {
        Cache::forget(self::FOOTER_CACHE_KEY);
    }

    private function path(string $url): string
    {
        return parse_url($url, PHP_URL_PATH) ?: '/';
    }
}
