<?php

namespace App\Models\Newsletter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un indirizzo dentro un invio. La chiave unica (campagna, iscritto) rende
 * ripetibile la costruzione della lista; il passaggio queued → sending,
 * condizionato, impedisce che due lotti spediscano la stessa riga.
 */
class NewsletterCampaignRecipient extends Model
{
    public const STATUS_QUEUED = 'queued';

    /**
     * Preso da un lotto, mail in partenza. Una riga rimasta qui dopo
     * un'interruzione ha un esito sconosciuto: non si rispedisce (meglio una
     * mail in meno che una doppia), la ripresa la chiude come fallita.
     */
    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /** Disiscritto o soppresso fra la costruzione della lista e il suo lotto. */
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'newsletter_campaign_id',
        'newsletter_subscriber_id',
        'status',
        'attempts',
        'message_id',
        'sent_at',
        'delivered_at',
        'opened_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NewsletterCampaign::class, 'newsletter_campaign_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscriber::class, 'newsletter_subscriber_id');
    }
}
