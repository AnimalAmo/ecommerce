<?php

namespace App\Listeners\Order\Concerns;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Invii post-ordine isolati, condivisi dai listener di OrderPaid e di
 * OnSiteOrderConfirmed. Con la coda sync il listener gira dentro la richiesta
 * del checkout: un mailer che esplode (SMTP giù, destinatario irraggiungibile)
 * non deve mostrare un errore a chi ha già pagato o prenotato, né bloccare gli
 * altri invii, né far fallire il job (un retry duplicherebbe le mail già partite).
 */
trait SendsOrderMails
{
    /** Invia riportando (report + log) ogni fallimento senza propagarlo. */
    private function sendSilently(Mailable $mailable, string $recipient, int $orderId): void
    {
        try {
            Mail::to($recipient)->send($mailable);
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('Mail post-ordine non inviata, il flusso prosegue', [
                'order_id' => $orderId,
                'mailable' => $mailable::class,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
