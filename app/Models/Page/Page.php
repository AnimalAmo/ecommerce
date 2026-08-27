<?php

namespace App\Models\Page;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Pagina di contenuto redazionale servita da DB (oggi: le due pagine legali).
 * Il testo nasce dai file in database/seeders/content/ ed è caricato dal
 * PageSeeder: git resta la fonte di verità, il DB la sorgente a runtime.
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

    /** @var array<int, string> */
    public array $translatable = ['title', 'body'];

    /** @var list<string> */
    protected $fillable = ['slug', 'title', 'body', 'last_updated_at'];

    public function titleFor(?string $locale = null): string
    {
        return $this->translationOr('title', $locale);
    }

    public function bodyFor(?string $locale = null): string
    {
        return $this->translationOr('body', $locale);
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
        return ['last_updated_at' => 'date'];
    }
}
