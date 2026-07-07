<?php

namespace App\Enums;

/**
 * Stato ordine (capture-first: nasce Pending nella pipeline e passa a Paid
 * nella stessa transaction quando il pagamento catturato viene registrato).
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('orders.status.'.$this->value);
    }
}
