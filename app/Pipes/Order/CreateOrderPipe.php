<?php

namespace App\Pipes\Order;

use App\Data\Checkout\OrderPipelineData;
use App\Enums\OrderStatus;
use App\Models\Order\Order;
use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Crea la testata ordine: snapshot buyer + totale in cents dal CartManager.
 * Nasce Pending e passa a Paid in CreateOrderPaymentPipe (online, capture-first)
 * o a Confirmed in ConfirmOnSiteOrderPipe (in struttura), nella stessa
 * transaction. user_id null = guest checkout permesso (solo online).
 *
 * La modalità si scrive qui una volta sola e non si ricava mai dal flag
 * attuale del partner, che può cambiare dopo l'ordine.
 */
class CreateOrderPipe
{
    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        $input = $data->input;

        $data->order = Order::create([
            'user_id' => Auth::id(),
            'status' => OrderStatus::Pending,
            'payment_mode' => $input->paymentMode,
            'partner_payment_url' => $input->partnerPaymentUrl,
            'checkout_token' => $input->checkoutToken,
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
