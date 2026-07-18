<?php

namespace App\Exceptions;

use App\Models\Order\Order;
use RuntimeException;

/**
 * Il capture (provider + gateway_session_id) ha GIÀ generato un OrderPayment:
 * esito IDEMPOTENTE — l'incasso appartiene all'ordine esistente, quindi il
 * chiamante NON deve stornare né creare un secondo ordine (replay del
 * callback via devtools).
 */
class OrderAlreadyPlacedException extends RuntimeException
{
    public function __construct(public readonly ?Order $order = null)
    {
        parent::__construct(__('payment.errors.already_placed'));
    }

    public static function forOrder(?Order $order): self
    {
        return new self($order);
    }
}
