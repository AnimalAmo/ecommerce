<?php

namespace App\Listeners\Order;

use App\Events\OnSiteOrderConfirmed;
use App\Listeners\Order\Concerns\SendsOrderMails;
use App\Mail\OrderConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Mail di una prenotazione da pagare in struttura (auto-discovery, come
 * SendOrderPaidMails). Qui non nasce nessun OrderPayment Completed, quindi
 * OrderPaymentObserver non emette OrderPaid: senza questo listener il cliente
 * non riceverebbe nulla. Niente mail regalo: il checkout offline non ammette
 * righe gift (PlaceOrderData::onSite, CartManager::addItem).
 */
class SendOnSiteOrderMails implements ShouldQueue
{
    use InteractsWithQueue, SendsOrderMails;

    /** Mai mail per prenotazioni poi rollbackate: il job si accoda al commit. */
    public bool $afterCommit = true;

    public function handle(OnSiteOrderConfirmed $event): void
    {
        $order = $event->order->loadMissing('items');

        $this->sendSilently(new OrderConfirmationMail($order), $order->email, $order->id);

        // Senza addebito Stripe il partner non ha altro modo di saperlo.
        $this->sendPartnerBookingMails($order);
    }
}
