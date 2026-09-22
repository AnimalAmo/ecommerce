<?php

namespace App\Pipes\Order;

use App\Data\Checkout\OrderPipelineData;
use App\Enums\OrderStatus;
use Closure;

/**
 * Chiude la prenotazione in struttura: Confirmed, non Paid. Conta come
 * prenotazione valida ma non come incasso, quindi resta fuori da registro
 * payout, GMV e speso del cliente, che filtrano tutti su Paid.
 */
class ConfirmOnSiteOrderPipe
{
    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        $data->order->update(['status' => OrderStatus::Confirmed]);

        return $next($data);
    }
}
