<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\OrderPayment\OrderPayment;

/**
 * Emette OrderPaid alla transizione del pagamento a Completed: sia in create
 * (pipeline capture-first, nasce già Completed) sia in update (riconciliazione
 * webhook di un pagamento Pending). Idempotente: un pagamento già Completed
 * risalvato non riemette — updated scatta solo se 'status' è cambiato davvero.
 */
class OrderPaymentObserver
{
    public function created(OrderPayment $payment): void
    {
        if ($payment->status === PaymentStatus::Completed) {
            $this->dispatchOrderPaid($payment);
        }
    }

    public function updated(OrderPayment $payment): void
    {
        if ($payment->status === PaymentStatus::Completed && $payment->wasChanged('status')) {
            $this->dispatchOrderPaid($payment);
        }
    }

    private function dispatchOrderPaid(OrderPayment $payment): void
    {
        event(new OrderPaid($payment->order));
    }
}
