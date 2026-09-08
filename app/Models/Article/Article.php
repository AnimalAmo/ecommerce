<?php

namespace App\Models\Article;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Articolo di Animal Times. Come per le pagine legali il testo nasce dai file
 * in database/seeders/content/articles/ ed è caricato dall'ArticleSeeder: git
 * resta la fonte di verità, il DB la sorgente a runtime.
 *
 * Le foto non hanno una colonna: si chiamano come lo slug e le produce lo
 * stesso script che converte i .docx, così un rename dello slug senza le
 * immagini corrispondenti si vede subito.
 */
class Article extends Model
{
    use HasTranslations;

    /** Lingua in cui la cliente scrive gli articoli: è lei a fare da rete. */
    public const SOURCE_LOCALE = 'it';

    /** Occhiello delle card: la griglia XD ne mostra 4 righe. */
    private const EXCERPT_LENGTH = 200;

    /** @var array<int, string> */
    public array $translatable = ['title', 'body'];

    /** @var list<string> */
    protected $fillable = ['slug', 'title', 'body', 'published_at'];

    /** Pubblicati, dal più recente: l'ordine della griglia. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereDate('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function titleFor(?string $locale = null): string
    {
        return $this->translationOr('title', $locale);
    }

    public function bodyFor(?string $locale = null): string
    {
        return $this->translationOr('body', $locale);
    }

    /** Primo paragrafo ripulito dai tag: nessuna colonna da tenere allineata al corpo. */
    public function excerptFor(?string $locale = null): string
    {
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

    public function cardImage(): string
    {
        return 'img/news/'.$this->slug.'.jpg';
    }

    public function heroImage(): string
    {
        return 'img/news/'.$this->slug.'-hero.jpg';
    }

    /**
     * spatie ricade su app.fallback_locale ('en'), che qui è la lingua
     * tradotta, non l'originale: se manca l'inglese l'articolo uscirebbe
     * vuoto. Il ripiego esplicito è l'italiano (stessa scelta di Page).
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
        return ['published_at' => 'date'];
    }
}
