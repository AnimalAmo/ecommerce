<?php

namespace App\Models\Faq;

use App\Services\Content\FaqService;
use Database\Factories\Faq\FaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory, HasTranslations;

    /**
     * Argomenti della pagina di assistenza (FAQ di piattaforma, faqable nullo),
     * nell'ordine della pagina. Le etichette stanno in lang/{it,en}/faq.php
     * (topics.*). Le FAQ agganciate a una scheda non hanno argomento.
     *
     * @var list<string>
     */
    public const TOPICS = ['bookings', 'smartbox', 'payments', 'partners', 'account'];

    /** @var array<int, string> */
    public array $translatable = ['question', 'answer'];

    protected static function booted(): void
    {
        // Il link "Domande frequenti" del piede dipende da una cache: ogni scrittura la invalida.
        static::saved(fn () => FaqService::forgetPlatformFlag());
        static::deleted(fn () => FaqService::forgetPlatformFlag());
    }

    protected $fillable = [
        'question',
        'answer',
        'position',
        'topic',
    ];

    /**
     * Le FAQ sono scritte in italiano: se manca l'inglese si mostra l'originale,
     * non una riga vuota (app.fallback_locale è 'en', la lingua tradotta).
     */
    public function getFallbackLocale(): string
    {
        return 'it';
    }

    /** FAQ della pagina di assistenza: non appartengono a nessuna scheda. */
    public function scopePlatform(Builder $query): Builder
    {
        return $query->whereNull('faqable_type');
    }

    /** FAQ agganciate a una scheda (struttura, evento o attività). */
    public function scopeForProducts(Builder $query): Builder
    {
        return $query->whereNotNull('faqable_type');
    }

    public function isPlatform(): bool
    {
        return $this->faqable_type === null;
    }

    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
