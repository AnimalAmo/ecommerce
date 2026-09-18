<?php

namespace App\Listeners;

use App\Models\MailDelivery;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Apre una riga mail_deliveries per ogni destinatario di ogni mail che parte
 * (auto-discovery, come MergeCartOnLogin). È l'aggancio del webhook Mailgun:
 * senza questa riga l'evento arriverebbe senza sapere di quale mail parla.
 *
 * Non deve mai far fallire l'invio: un errore qui finisce nei log, la mail
 * resta partita.
 */
class RecordMailDelivery
{
    public function handle(MessageSent $event): void
    {
        try {
            $messageId = trim($event->sent->getMessageId(), '<>');

            foreach ($event->message->getTo() as $address) {
                MailDelivery::updateOrCreate(
                    ['message_id' => $messageId, 'recipient' => $address->getAddress()],
                    [
                        'mailable' => $event->data['__laravel_mailable'] ?? null,
                        'status' => MailDelivery::STATUS_SENT,
                    ],
                );
            }
        } catch (Throwable $exception) {
            Log::warning('Registro consegne non aggiornato', ['error' => $exception->getMessage()]);
        }
    }
}
