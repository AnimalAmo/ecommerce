<?php

namespace App\Listeners\Order\Concerns;

use App\Mail\PartnerNewBookingMail;
use App\Models\Order\Order;
use App\Models\User;
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
    /**
     * Una mail per ogni partner distinto delle righe. Oggi un carrello ha un
     * solo venditore, ma le righe della piattaforma (partner_user_id null) non
     * hanno nessuno da avvisare e un utente senza email non è raggiungibile.
     */
    private function sendPartnerBookingMails(Order $order): void
    {
        $partnerIds = $order->items->pluck('partner_user_id')->filter()->unique()->values();

        if ($partnerIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereKey($partnerIds->all())
            ->get()
            ->filter(fn (User $partner): bool => filled($partner->email))
            ->each(function (User $partner) use ($order): void {
                $this->sendSilently(new PartnerNewBookingMail($order, $partner), $partner->email, $order->id);
            });
    }

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
