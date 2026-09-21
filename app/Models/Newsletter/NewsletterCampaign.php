<?php

namespace App\Models\Newsletter;

use App\Models\User;
use Database\Factories\Newsletter\NewsletterCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Un numero della newsletter: oggetto, anteprima nella casella e testo in
 * italiano e inglese; pubblico, ritmo di invio e contatori.
 *
 * Ciclo di vita: draft → sending → sent (o failed se nessuna mail è partita).
 */
class NewsletterCampaign extends Model
{
    /** @use HasFactory<NewsletterCampaignFactory> */
    use HasFactory, HasTranslations;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_IT = 'it';

    public const AUDIENCE_EN = 'en';

    /** Ritmi proposti dal pannello: invii all'ora, 0 = tutti subito. */
    public const HOURLY_RATES = [200, 500, 0];

    /** @var array<int, string> */
    public array $translatable = ['subject', 'preheader', 'body'];

    protected $fillable = [
        'subject',
        'preheader',
        'body',
        'audience',
        'hourly_rate',
        'status',
        'recipients_count',
        'sent_count',
        'delivered_count',
        'opened_count',
        'failed_count',
        'test_sent_to',
        'test_sent_at',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'integer',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'delivered_count' => 'integer',
            'opened_count' => 'integer',
            'failed_count' => 'integer',
            'test_sent_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Si scrive in italiano: una versione inglese vuota manda l'originale, non
     * una mail senza testo (app.fallback_locale è 'en').
     */
    public function getFallbackLocale(): string
    {
        return 'it';
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /** Il pubblico comprende iscritti in inglese? */
    public function reachesEnglish(): bool
    {
        return $this->audience !== self::AUDIENCE_IT;
    }

    /** Versione della lingua compilata (oggetto e testo), senza ripiego sull'italiano. */
    public function hasVersion(string $locale): bool
    {
        return filled($this->getTranslation('subject', $locale, false))
            && filled(trim(strip_tags((string) $this->getTranslation('body', $locale, false))));
    }

    /**
     * Base della percentuale di aperture: le consegne confermate dal webhook
     * se ne sono arrivate, altrimenti le mail partite.
     */
    public function openRateBase(): int
    {
        return $this->delivered_count > 0 ? $this->delivered_count : $this->sent_count;
    }

    public function openRate(): ?int
    {
        $base = $this->openRateBase();

        return $base > 0 ? (int) round($this->opened_count / $base * 100) : null;
    }
}
