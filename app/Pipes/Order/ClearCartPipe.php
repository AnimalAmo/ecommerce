<?php

namespace App\Pipes\Order;

use App\Data\Cart\CartItemData;
use App\Data\Checkout\OrderPipelineData;
use App\Services\Cart\CartManager;
use Closure;

/**
 * Rimuove dal carrello SOLO le righe del flusso ordinato (per chiave, dallo
 * snapshot in input) — mai clear() totale: flusso regalo e flusso normale sono
 * carrelli separati filtrati da is_gift e l'altro flusso deve sopravvivere.
 */
class ClearCartPipe
{
    public function __construct(
        private readonly CartManager $cart,
    ) {}

    public function handle(OrderPipelineData $data, Closure $next): mixed
    {
        $data->input->items->each(
            fn (CartItemData $item) => $this->cart->removeItem($item->key),
        );

        return $next($data);
    }
}
