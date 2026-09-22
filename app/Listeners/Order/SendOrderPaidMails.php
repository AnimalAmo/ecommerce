<?php

namespace App\Listeners\Order;

use App\Events\OrderPaid;
use App\Listeners\Order\Concerns\SendsOrderMails;
use App\Mail\OrderConfirmationMail;
use App\Mail\SmartboxGiftMail;
use App\Models\OrderItem\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Mail post-incasso (auto-discovery, come MergeCartOnLogin): conferma ordine
 * al buyer + una mail regalo per ogni riga gift con destinatario nelle
 * options + "nuova prenotazione" a ogni partner delle righe. In coda e
 * afterCommit: parte solo a ordine visibile a db (la
 * pipeline gira in DB::transaction).
 */
class SendOrderPaidMails implements ShouldQueue
{
    use InteractsWithQueue, SendsOrderMails;

    /** Mai mail per ordini poi rollbackati: il job si accoda al commit. */
    public bool $afterCommit = true;

    public function handle(OrderPaid $event): void
    {
        $order = $event->order->loadMissing('items');

        // Ogni invio è isolato (SendsOrderMails::sendSilently): chi ha già
        // pagato non deve vedere l'errore di un mailer.
        $this->sendSilently(new OrderConfirmationMail($order), $order->email, $order->id);

        $order->items
            ->filter(fn (OrderItem $item): bool => $item->is_gift
                && filled($item->options['gift']['recipient_email'] ?? null))
            ->each(function (OrderItem $item) use ($order): void {
                $this->sendSilently(new SmartboxGiftMail($item), $item->options['gift']['recipient_email'], $order->id);
            });

        $this->sendPartnerBookingMails($order);
    }
}
