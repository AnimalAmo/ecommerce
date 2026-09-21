<?php

namespace App\Models\Page;

use App\Services\Content\PageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Pagina di contenuto redazionale servita da DB.
 *
 * - `legal`: le tre pagine legali. Il testo nasce dai file in
 *   database/seeders/content/ (PageSeeder, solo alla prima semina) e poi si
 *   modifica dal pannello; lo slug è fisso perché ognuna ha la sua rotta.
 * - `free`: le "pagine libere" che la cliente crea dal pannello, servite da
 *   /pagina/{slug}.
 */
class Page extends Model
{
    use HasTranslations;

    public const TERMS_CUSTOMERS = 'termini-e-condizioni';

    public const TERMS_SUPPLIERS = 'termini-e-condizioni-fornitori';

    /** Una sola informativa per utenti e strutture: il documento copre entrambi. */
    public const PRIVACY = 'privacy-policy';

    /** Lingua in cui i documenti sono redatti: è lei a fare da rete. */
    public const SOURCE_LOCALE = 'it';

    public const KIND_LEGAL = 'legal';

    public const KIND_FREE = 'free';

    /**
     * Pagine legali: slug => nome della rotta che le serve.
     *
     * @var array<string, string>
     */
    public const LEGAL_ROUTES = [
        self::TERMS_CUSTOMERS => 'terms.customers',
        self::TERMS_SUPPLIERS => 'terms.suppliers',
        self::PRIVACY => 'privacy',
    ];

    /**
     * Colonne del piede del sito in cui una pagina può comparire. Il design
     * parla di "Azienda" e "Sicurezza"; il piede reale ha "Azienda" e
     * "Help & Support", e la seconda è il posto naturale per regole e garanzie.
     * Le etichette del pannello stanno in admin-content.pages.footer_columns.
     *
     * @var list<string>
     */
    public const FOOTER_COLUMNS = ['company', 'support'];

    /** @var array<int, string> */
    public array $translatable = ['title', 'body'];

    /** @var list<string> */
    protected $fillable = ['slug', 'title', 'body', 'last_updated_at', 'kind', 'footer_column', 'is_published'];

    protected $attributes = [
        'kind' => self::KIND_LEGAL,
        'is_published' => true,
    ];

    protected static function booted(): void
    {
        // Il piede del sito legge i link da una cache: ogni scrittura la invalida.
        static::saved(fn () => PageService::forgetFooterLinks());
        static::deleted(fn () => PageService::forgetFooterLinks());
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_FREE);
    }

    public function scopeLegal(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_LEGAL);
    }

    public function isLegal(): bool
    {
        return $this->kind === self::KIND_LEGAL;
    }

    public function titleFor(?string $locale = null): string
    {
        return $this->translationOr('title', $locale);
    }

    public function bodyFor(?string $locale = null): string
    {
        return $this->translationOr('body', $locale);
    }

    /** L'indirizzo pubblico: la rotta fissa per le legali, /pagina/{slug} per le libere. */
    public function publicUrl(): ?string
    {
        if ($this->isLegal()) {
            $route = self::LEGAL_ROUTES[$this->slug] ?? null;

            return $route === null ? null : route($route);
        }

        return route('page', ['slug' => $this->slug]);
    }

    /**
     * spatie ricade su app.fallback_locale ('en'), che qui è la lingua
     * tradotta, non l'originale: se manca l'inglese la pagina uscirebbe
     * vuota. Il ripiego esplicito è l'italiano, la lingua dei documenti.
     */
    private function translationOr(string $key, ?string $locale): string
    {
        $locale ??= app()->getLocale();

        return $this->getTranslation($key, $locale, false)
            ?: $this->getTranslation($key, self::SOURCE_LOCALE, false);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_updated_at' => 'date',
            'is_published' => 'boolean',
        ];
    }
}
