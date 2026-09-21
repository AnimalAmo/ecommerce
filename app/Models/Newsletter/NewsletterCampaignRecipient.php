<?php

namespace App\Models\Newsletter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un indirizzo dentro un invio. La chiave unica (campagna, iscritto) rende
 * ripetibile la costruzione della lista, e lo stato `sent` impedisce la
 * seconda copia quando un lotto riparte dopo un'interruzione.
 */
class NewsletterCampaignRecipient extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /** Disiscritto o soppresso fra la costruzione della lista e il suo lotto. */
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'newsletter_campaign_id',
        'newsletter_subscriber_id',
        'status',
        'message_id',
        'sent_at',
        'delivered_at',
        'opened_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
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
