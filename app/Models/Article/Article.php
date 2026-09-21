<?php

namespace App\Models\Article;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Articolo di Animal Times. I quattro articoli della consegna di settembre
 * nascono dai file in database/seeders/content/articles/ (ArticleSeeder, solo
 * alla prima semina); da lì in poi il database è la fonte di verità e la
 * cliente li scrive dal pannello.
 *
 * La copertina è la media collection `cover` (un file solo): card e pagina
 * dell'articolo leggono le conversioni `card` e `hero`, mai un path a mano.
 */
class Article extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    public const COVER = 'cover';

    /** Stati nel pannello: la data di pubblicazione decide tutto. */
    public const DRAFT = 'draft';

    public const SCHEDULED = 'scheduled';

    public const PUBLISHED = 'published';

    /** Categorie del magazine (etichette in admin-content.articles.categories). */
    public const CATEGORIES = ['travel', 'puppies', 'partners'];

    /** Lingua in cui la cliente scrive gli articoli: è lei a fare da rete. */
    public const SOURCE_LOCALE = 'it';

    /** Occhiello delle card: la griglia XD ne mostra 4 righe. */
    private const EXCERPT_LENGTH = 200;

    /** @var array<int, string> */
    public array $translatable = ['title', 'excerpt', 'body', 'cover_alt'];

    /** @var list<string> */
    protected $fillable = ['slug', 'title', 'excerpt', 'body', 'published_at', 'category', 'cover_alt', 'author_id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COVER)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Non in coda: la card deve esistere appena la cliente salva, e sono due
     * ritagli di un'immagine sola.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // Griglie di home, /news e correlati: il ritaglio 960x495 delle card XD.
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 960, 495)
            ->performOnCollections(self::COVER)
            ->nonQueued();

        // Pagina dell'articolo: riquadro 620x451 (object-cover), doppio per gli
        // schermi densi. Max e non Crop: una foto più piccola non si ingrandisce.
        $this->addMediaConversion('hero')
            ->fit(Fit::Max, 1240, 1240)
            ->performOnCollections(self::COVER)
            ->nonQueued();
    }

    /** Pubblicati, dal più recente: l'ordine della griglia. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereDate('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** In bozza senza data, programmato con una data futura, pubblicato altrimenti. */
    public function status(): string
    {
        return match (true) {
            $this->published_at === null => self::DRAFT,
            $this->published_at->isAfter(today()) => self::SCHEDULED,
            default => self::PUBLISHED,
        };
    }

    public function isPublished(): bool
    {
        return $this->status() === self::PUBLISHED;
    }

    /** Lingue in cui l'articolo è scritto davvero (titolo e testo), non per ripiego. */
    public function hasLocale(string $locale): bool
    {
        return trim((string) $this->getTranslation('title', $locale, false)) !== ''
            && trim((string) $this->getTranslation('body', $locale, false)) !== '';
    }

    public function titleFor(?string $locale = null): string
    {
        return $this->translationOr('title', $locale);
    }

    public function bodyFor(?string $locale = null): string
    {
        return $this->translationOr('body', $locale);
    }

    /**
     * Le righe sotto il titolo nelle card: il sommario scritto dalla cliente
     * o, se manca, il primo paragrafo ripulito dai tag. Una lingua senza
     * testo proprio ripiega per intero sull'italiano, sommario compreso.
     */
    public function excerptFor(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if (! $this->hasOwnBody($locale)) {
            $locale = self::SOURCE_LOCALE;
        }

        $written = trim((string) $this->getTranslation('excerpt', $locale, false));

        if ($written !== '') {
            return $written;
        }

        preg_match('/<p>(.*?)<\/p>/su', $this->bodyFor($locale), $matches);

        $text = html_entity_decode(strip_tags($matches[1] ?? ''), ENT_QUOTES);

        return Str::limit(trim($text), self::EXCERPT_LENGTH - 1, '…');
    }

    /**
     * Data come nell'XD ("5 Ottobre 2023"): Carbon rende il mese italiano in
     * minuscolo, l'iniziale va rimessa. In inglese è già maiuscolo.
     */
    public function formattedDate(?string $locale = null): string
    {
        $date = $this->published_at->locale($locale ?? app()->getLocale());

        return $date->translatedFormat('j').' '.Str::ucfirst($date->translatedFormat('F')).' '.$date->translatedFormat('Y');
    }

    /** URL della copertina nella conversione chiesta, o null se l'articolo non ne ha. */
    public function coverUrl(string $conversion = 'card'): ?string
    {
        return $this->getFirstMedia(self::COVER)?->getUrl($conversion);
    }

    /** Testo alternativo della copertina: quello scritto dalla cliente, o il titolo. */
    public function coverAlt(?string $locale = null): string
    {
        return $this->translationOr('cover_alt', $locale) ?: $this->titleFor($locale);
    }

    private function hasOwnBody(string $locale): bool
    {
        return trim((string) $this->getTranslation('body', $locale, false)) !== '';
    }

    /**
     * spatie ricade su app.fallback_locale ('en'), che qui è la lingua
     * tradotta, non l'originale: se manca l'inglese l'articolo uscirebbe
     * vuoto. Il ripiego esplicito è l'italiano (stessa scelta di Page).
     */
    private function translationOr(string $key, ?string $locale): string
    {
        $locale ??= app()->getLocale();

        return (string) ($this->getTranslation($key, $locale, false)
            ?: $this->getTranslation($key, self::SOURCE_LOCALE, false));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['published_at' => 'date'];
    }
}
