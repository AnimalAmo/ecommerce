<?php

namespace App\Services\Mail;

use App\Models\MailDelivery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Traduce un evento Mailgun in una riga di mail_deliveries.
 *
 * Sta qui e non nel controller perché è l'unico punto del progetto che conosce
 * il vocabolario di Mailgun (nomi degli eventi, `severity`, dove sta il motivo
 * dello scarto): il giorno che si aggiunge una seconda sorgente — un comando di
 * riallineamento, un reinvio manuale — riusa questa classe invece di ricopiare
 * la mappatura dentro un altro controller.
 */
class RecordMailgunEvent
{
    /**
     * Eventi che cambiano lo stato. Aperture e click non sono uno stato di
     * consegna: le aperture delle newsletter (le sole mail con il tracking
     * acceso) le conta NewsletterFeedback.
     */
    private const STATUSES = [
        'accepted' => MailDelivery::STATUS_ACCEPTED,
        'delivered' => MailDelivery::STATUS_DELIVERED,
        'failed' => MailDelivery::STATUS_FAILED,
        'rejected' => MailDelivery::STATUS_FAILED,
        'complained' => MailDelivery::STATUS_COMPLAINED,
    ];

    /**
     * Null quando l'evento non ci riguarda o è più vecchio di quello già
     * registrato: per il chiamante è un 200 comunque, non un errore.
     *
     * @param  array<string, mixed>  $event  il blocco `event-data` del payload
     */
    public function record(array $event): ?MailDelivery
    {
        $status = $this->statusOf($event);
        $messageId = trim((string) data_get($event, 'message.headers.message-id'), '<>');
        $recipient = (string) ($event['recipient'] ?? '');

        if ($status === null || $messageId === '' || $recipient === '') {
            return null;
        }

        $delivery = MailDelivery::firstOrNew([
            'message_id' => $messageId,
            'recipient' => $recipient,
        ]);

        // Evento più vecchio di quello già registrato: si scarta, ma la riga
        // esiste comunque (mail partita da un altro ambiente sullo stesso dominio).
        if ($delivery->exists && MailDelivery::rank($status) < MailDelivery::rank((string) $delivery->status)) {
            return null;
        }

        $delivery->fill([
            'status' => $status,
            'severity' => $event['severity'] ?? null,
            'smtp_code' => ($code = data_get($event, 'delivery-status.code')) === null ? null : (string) $code,
            'reason' => data_get($event, 'delivery-status.message')
                ?: data_get($event, 'reject.reason')
                ?: ($event['reason'] ?? null),
            'last_event_at' => isset($event['timestamp'])
                ? Carbon::createFromTimestamp((float) $event['timestamp'])
                : now(),
        ])->save();

        if ($delivery->failed()) {
            Log::warning('Mail non consegnata', [
                'recipient' => $recipient,
                'mailable' => $delivery->mailable,
                'reason' => $delivery->reason,
            ]);
        }

        return $delivery;
    }

    /**
     * Mailgun usa lo stesso evento `failed` per lo scarto definitivo e per il
     * rinvio temporaneo: li distingue solo `severity`.
     *
     * @param  array<string, mixed>  $event
     */
    private function statusOf(array $event): ?string
    {
        $status = self::STATUSES[(string) ($event['event'] ?? '')] ?? null;

        if ($status === MailDelivery::STATUS_FAILED && ($event['severity'] ?? null) === 'temporary') {
            return MailDelivery::STATUS_DEFERRED;
        }

        return $status;
    }
}
