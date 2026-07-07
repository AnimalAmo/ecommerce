<?php

namespace App\Pipes\Order;

use App\Data\Checkout\OrderPipelineData;
use App\Enums\OrderStatus;
use App\Models\Order\Order;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Crea la testata ordine: snapshot buyer + totale in cents dal CartManager.
 * Nasce Pending e passa a Paid in CreateOrderPaymentPipe (capture-first,
 * stessa transaction). user_id null = guest checkout permesso.
 */
class CreateOrderPipe
{
    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        $input = $data->input;

        $data->order = Order::create([
            'user_id' => Auth::id(),
            'status' => OrderStatus::Pending,
            'is_gift' => $input->gift,
            'first_name' => $input->firstName,
            'last_name' => $input->lastName,
            'email' => $input->email,
            'phone' => $input->phone,
            'country' => $input->country,
            'total_cents' => $input->totalCents,
        ]);

        return $next($data);
    }
}
