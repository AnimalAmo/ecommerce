<?php

namespace App\Events;

use App\Models\Order\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ordine incassato: emesso da OrderPaymentObserver quando il pagamento
 * transita a Completed (una sola volta per transizione — idempotente).
 */
class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}
}
