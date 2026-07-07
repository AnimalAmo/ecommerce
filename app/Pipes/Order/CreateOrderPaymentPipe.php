<?php

namespace App\Pipes\Order;

use App\Data\Checkout\OrderPipelineData;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Closure;

/**
 * Registra il pagamento già catturato (capture-first: quando la pipeline gira
 * l'incasso è verificato server-side) e porta l'ordine a Paid nella stessa
 * transaction. La create con status Completed fa scattare OrderPaymentObserver
 * → OrderPaid → mail (afterCommit).
 */
class CreateOrderPaymentPipe
{
    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        $input = $data->input;

        $data->order->update(['status' => OrderStatus::Paid]);

        $data->order->payment()->create([
            'payment_method' => $input->paymentMethod,
            'status' => PaymentStatus::Completed,
            'amount_cents' => $input->totalCents,
            'transaction_id' => $input->capture->transactionId,
            'gateway_session_id' => $input->capture->gatewaySessionId,
            'provider' => $input->capture->provider,
            'provider_response' => $input->capture->providerResponse,
            'paid_at' => now(),
        ]);

        return $next($data);
    }
}
