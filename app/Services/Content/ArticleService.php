<?php

namespace App\Services\Content;

use App\Models\Article\Article;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Articoli di Animal Times: elenco del pannello, scrittura, copertine.
 *
 * Lo stato dipende solo da published_at: nulla = bozza, futura =
 * programmato (esce da solo quel giorno, lo scope `published` del sito lo
 * tiene fuori fino ad allora), passata o oggi = pubblicato.
 */
class ArticleService
{
    /** Foto versionate degli articoli della consegna di settembre, una per slug. */
    public static function seedCoverPath(string $slug): string
    {
        return database_path("seeders/content/articles/{$slug}.jpg");
    }

    /**
     * Elenco del pannello, dal più recente (le bozze in testa). Gli articoli
     * sono decine: la ricerca sul titolo (JSON tradotto) si fa in memoria, che
     * è l'unico modo di averla insensibile alle maiuscole anche su MySQL.
     *
     * @param  array{search?: string, status?: string, category?: string}  $filters
     * @return Collection<int, Article>
     */
    public function list(array $filters = []): Collection
    {
        $needle = mb_strtolower(trim($filters['search'] ?? ''));
        $status = $filters['status'] ?? '';
        $category = $filters['category'] ?? '';

        return Article::query()
            ->with('media')
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->orderByRaw('published_at is null desc')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->when($status !== '', fn (Collection $articles) => $articles->filter(
                fn (Article $article): bool => $article->status() === $status,
            ))
            ->when($needle !== '', fn (Collection $articles) => $articles->filter(
                fn (Article $article): bool => collect($article->getTranslations('title'))
                    ->contains(fn ($title): bool => str_contains(mb_strtolower((string) $title), $needle)),
            ))
            ->values();
    }

    /** @return array{published: int, drafts: int} */
    public function totals(): array
    {
        return [
            'published' => Article::query()->whereDate('published_at', '<=', today())->count(),
            'drafts' => Article::query()->whereNull('published_at')->count(),
        ];
    }

    /**
     * Crea o aggiorna un articolo. `published_at` nullo lo mette in bozza.
     * I testi passano da HtmlSanitizer (il corpo esce con {!! !!}); una lingua
     * lasciata vuota viene tolta, e il sito ripiega sull'italiano.
     *
     * @param  array{title: array<string, string|null>, excerpt?: array<string, string|null>, body: array<string, string|null>, cover_alt?: array<string, string|null>, category?: string|null, slug?: string|null, published_at?: string|null}  $data
     */
    public function save(?Article $article, array $data, ?User $editor = null): Article
    {
        $article ??= new Article(['author_id' => $editor?->id]);

        foreach (['it', 'en'] as $locale) {
            $this->translate($article, 'title', $locale, trim((string) ($data['title'][$locale] ?? '')));
            $this->translate($article, 'excerpt', $locale, trim((string) ($data['excerpt'][$locale] ?? '')));
            $this->translate($article, 'body', $locale, HtmlSanitizer::clean($data['body'][$locale] ?? ''));
            $this->translate($article, 'cover_alt', $locale, trim((string) ($data['cover_alt'][$locale] ?? '')));
        }

        // title/body sono json NOT NULL: una lingua tolta non deve lasciarle null.
        foreach (['title', 'body'] as $attribute) {
            if ($article->getTranslations($attribute) === []) {
                $article->setAttribute($attribute, []);
            }
        }

        $category = (string) ($data['category'] ?? '');
        $article->category = in_array($category, Article::CATEGORIES, true) ? $category : null;
        $article->slug = $this->uniqueSlug($article, (string) ($data['slug'] ?? ''));
        $article->published_at = filled($data['published_at'] ?? null) ? Carbon::parse($data['published_at'])->startOfDay() : null;

        $article->save();

        return $article;
    }

    /**
     * Sostituisce la copertina (la collection tiene un file solo).
     *
     * L'estensione viene dal tipo letto nel contenuto, mai dal nome scelto da
     * chi carica: un PNG chiamato .html finirebbe sul disco pubblico e il web
     * server lo servirebbe come pagina. La collection accetta solo questi tre
     * tipi, quindi il ripiego su jpg non salva mai altro.
     */
    public function replaceCover(Article $article, UploadedFile $file): void
    {
        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $article->addMedia($file)
            ->usingFileName($article->slug.'.'.$extension)
            ->toMediaCollection(Article::COVER);
    }

    /** Riporta in bozza: il sito smette di mostrarlo, il testo resta. */
    public function unpublish(Article $article): void
    {
        $article->published_at = null;
        $article->save();
    }

    /** Cancella l'articolo; la media library toglie la copertina e i ritagli. */
    public function delete(Article $article): void
    {
        $article->delete();
    }

    /**
     * Lo slug come verrà salvato: quello scritto, normalizzato, oppure quello
     * del titolo italiano, reso unico con un suffisso se serve.
     */
    public function uniqueSlug(Article $article, string $wanted): string
    {
        $base = Str::slug(trim($wanted, " /\t\n"), '-', 'it')
            ?: Str::slug((string) $article->getTranslation('title', 'it', false), '-', 'it')
            ?: 'articolo';
        $base = Str::limit($base, 110, '');

        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($slug, $article->id)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function slugTaken(string $slug, ?int $ignoreId = null): bool
    {
        return Article::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Mette nella collection `cover` la foto versionata dell'articolo, se ne
     * esiste una e l'articolo non ha già una copertina (che vince sempre: è
     * quella scelta dalla cliente). La usano l'ArticleSeeder su un database
     * nuovo e la migration che ha tolto le foto da public/img/news.
     */
    public function importSeedCover(Article $article): bool
    {
        $path = self::seedCoverPath($article->slug);

        if (! is_file($path) || $article->hasMedia(Article::COVER)) {
            return false;
        }

        $article->addMedia($path)
            ->preservingOriginal()
            ->toMediaCollection(Article::COVER);

        return true;
    }

    /**
     * importSeedCover() su tutti gli articoli, dal più vecchio. Un articolo
     * che non riceve niente (nessun jpg per il suo slug, o una copertina già
     * presente perché un passaggio precedente si è interrotto) non ferma gli
     * altri: per questo un foreach e non each(), che si arresta al primo
     * `false` restituito dal callback.
     *
     * @return int quante copertine sono state caricate
     */
    public function importSeedCovers(): int
    {
        $imported = 0;

        foreach (Article::query()->lazyById() as $article) {
            if ($this->importSeedCover($article)) {
                $imported++;
            }
        }

        return $imported;
    }

    private function translate(Article $article, string $attribute, string $locale, string $value): void
    {
        $value === ''
            ? $article->forgetTranslation($attribute, $locale)
            : $article->setTranslation($attribute, $locale, $value);
    }
}
