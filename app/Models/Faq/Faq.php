<?php

namespace App\Models\Faq;

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
     * Argomenti della pagina di assistenza (FAQ di piattaforma, faqable nullo).
     * Le FAQ agganciate a una scheda non hanno argomento.
     */
    public const TOPICS = [
        'bookings' => 'Prenotazioni',
        'smartbox' => 'Smartbox',
        'payments' => 'Pagamenti',
        'partners' => 'Partner',
        'account' => 'Account',
    ];

    /** @var array<int, string> */
    public array $translatable = ['question', 'answer'];

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

    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
