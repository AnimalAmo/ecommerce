<?php

namespace App\Data\Checkout;

use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use Illuminate\Support\Collection;

/**
 * Carrier mutabile della pipeline ordine (plain PHP, stile matsuri): l'input
 * immutabile più lo stato accumulato dai pipe (ordine e righe create).
 */
class OrderPipelineData
{
    public ?Order $order = null;

    /** @var Collection<int, OrderItem> */
    public Collection $orderItems;

    public function __construct(
        public readonly PlaceOrderData $input,
    ) {
        $this->orderItems = new Collection;
    }
}
