<?php

namespace App\Models\Newsletter;

use App\Models\User;
use Database\Factories\Newsletter\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un indirizzo della lista, con la prova del consenso: testo accettato, IP e
 * user agent della richiesta, poi IP e user agent del clic di conferma.
 *
 * Non è un utente: ci si iscrive anche senza account (form nel piede del
 * sito). `user_id` lega l'iscritto all'account quando esiste, per tenere
 * allineato `users.newsletter`.
 */
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    /** Rimbalzo definitivo: l'indirizzo non esiste. Lista di soppressione. */
    public const STATUS_BOUNCED = 'bounced';

    /** Segnalato come spam. Lista di soppressione. */
    public const STATUS_COMPLAINED = 'complained';

    public const SOURCE_FOOTER = 'footer';

    public const SOURCE_REGISTRATION = 'registration';

    public const SOURCE_PROFILE = 'profile';

    /** Vecchia casella di registrazione (users.newsletter), senza prova. */
    public const SOURCE_LEGACY = 'legacy';

    public const SOURCE_ADMIN = 'admin';

    /** Stati che non ricevono mai più nulla, nemmeno una nuova conferma. */
    public const SUPPRESSED = [self::STATUS_BOUNCED, self::STATUS_COMPLAINED];

    protected $fillable = [
        'email',
        'user_id',
        'locale',
        'status',
        'source',
        'legacy',
        'token',
        'consent_text',
        'consent_ip',
        'consent_user_agent',
        'requested_at',
        'confirmation_sent_at',
        'confirmed_at',
        'confirmation_ip',
        'confirmation_user_agent',
        'unsubscribed_at',
        'suppressed_at',
    ];

    protected function casts(): array
    {
        return [
            'legacy' => 'boolean',
            'requested_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'suppressed_at' => 'datetime',
        ];
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /** Contatti della vecchia casella mai contattati: il bersaglio di "Manda la conferma". */
    public function scopeLegacyNeverContacted(Builder $query): Builder
    {
        return $query->where('legacy', true)
            ->where('status', self::STATUS_PENDING)
            ->whereNull('confirmation_sent_at');
    }

    /** Iscritti che ricevono una campagna con quel pubblico (all | it | en). */
    public function scopeAudience(Builder $query, string $audience): Builder
    {
        $query->confirmed();

        return match ($audience) {
            NewsletterCampaign::AUDIENCE_IT => $query->where('locale', 'it'),
            NewsletterCampaign::AUDIENCE_EN => $query->where('locale', 'en'),
            default => $query,
        };
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isSuppressed(): bool
    {
        return in_array($this->status, self::SUPPRESSED, true);
    }
}
