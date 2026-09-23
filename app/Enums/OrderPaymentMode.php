<?php

namespace App\Enums;

/**
 * Come si paga un ordine. Si modella sull'ordine e non come PaymentMethod:
 * ogni caso di PaymentMethod è Stripe, e uno "in struttura" lì verrebbe
 * montato come wallet Express Checkout.
 */
enum OrderPaymentMode: string
{
    case Online = 'online';
    case OnSite = 'on_site';

    public function label(): string
    {
        return __('orders.payment_mode.'.$this->value);
    }
}
