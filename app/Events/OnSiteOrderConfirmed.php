<?php

namespace App\Events;

use App\Models\Order\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Prenotazione "paga in struttura" registrata: emesso da PlaceOrderAction dopo
 * il commit. È il gemello di OrderPaid per gli ordini senza pagamento, che non
 * passano mai da OrderPaymentObserver e altrimenti non manderebbero nessuna mail.
 */
class OnSiteOrderConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {}
}
