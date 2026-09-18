<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Esito di una mail in uscita. La riga nasce da RecordMailDelivery (messaggio
 * consegnato al transport) e viene aggiornata dal webhook Mailgun.
 */
class MailDelivery extends Model
{
    public const STATUS_SENT = 'sent';

    public const STATUS_ACCEPTED = 'accepted';

    /** Rifiuto temporaneo: la casella era piena o il server occupato, Mailgun ritenta per ore. */
    public const STATUS_DEFERRED = 'deferred';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_COMPLAINED = 'complained';

    protected $fillable = [
        'message_id',
        'recipient',
        'mailable',
        'status',
        'severity',
        'smtp_code',
        'reason',
        'last_event_at',
    ];

    protected function casts(): array
    {
        return [
            'last_event_at' => 'datetime',
        ];
    }

    /**
     * Gli eventi Mailgun non arrivano in ordine (un `accepted` può seguire un
     * `delivered`): il rango evita che un evento vecchio riporti indietro lo
     * stato.
     *
     * `deferred` sta *sotto* `delivered` apposta: un rifiuto temporaneo viene
     * ritentato per ore e quasi sempre finisce in consegna — se pesasse quanto
     * un fallimento definitivo, la riga resterebbe rossa per sempre e la mail
     * risulterebbe non arrivata mentre era arrivata.
     */
    public static function rank(string $status): int
    {
        return match ($status) {
            self::STATUS_SENT => 0,
            self::STATUS_ACCEPTED => 1,
            self::STATUS_DEFERRED => 2,
            self::STATUS_DELIVERED => 3,
            self::STATUS_FAILED => 4,
            default => 5,
        };
    }

    public function failed(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_COMPLAINED], true);
    }
}
