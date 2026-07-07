<?php

namespace App\Listeners\Order;

use App\Events\OrderPaid;
use App\Mail\OrderConfirmationMail;
use App\Mail\SmartboxGiftMail;
use App\Models\OrderItem\OrderItem;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Mail post-incasso (auto-discovery, come MergeCartOnLogin): conferma ordine
 * al buyer + una mail regalo per ogni riga gift con destinatario nelle
 * options. In coda e afterCommit: parte solo a ordine visibile a db (la
 * pipeline gira in DB::transaction).
 */
class SendOrderPaidMails implements ShouldQueue
{
    use InteractsWithQueue;

    /** Mai mail per ordini poi rollbackati: il job si accoda al commit. */
    public bool $afterCommit = true;

    public function handle(OrderPaid $event): void
    {
        $order = $event->order->loadMissing('items');

        // Ogni invio è isolato: con la coda sync il listener gira in-process
        // dentro handlePaymentCallback, quindi un mailer che esplode (SMTP giù,
        // destinatario irraggiungibile) NON deve far vedere un errore a chi ha
        // già pagato, né bloccare gli altri invii, né far fallire il job (un
        // retry duplicherebbe le mail già partite).
        $this->sendSilently(new OrderConfirmationMail($order), $order->email, $order->id);

        $order->items
            ->filter(fn (OrderItem $item): bool => $item->is_gift
                && filled($item->options['gift']['recipient_email'] ?? null))
            ->each(function (OrderItem $item) use ($order): void {
                $this->sendSilently(new SmartboxGiftMail($item), $item->options['gift']['recipient_email'], $order->id);
            });
    }

    /** Invia riportando (report + log) ogni fallimento senza propagarlo. */
    private function sendSilently(Mailable $mailable, string $recipient, int $orderId): void
    {
        try {
            Mail::to($recipient)->send($mailable);
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('Mail post-pagamento non inviata, il flusso prosegue', [
                'order_id' => $orderId,
                'mailable' => $mailable::class,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
