<?php

namespace App\Data\Cart;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

/**
 * Fotografia del carrello: id della riga carts (null per il guest in sessione),
 * righe presentate e totale in integer cents (display solo via Format::money).
 * DTO spatie/laravel-data (decisione ratificata 2026-07-06).
 */
final class CartData extends Data
{
    /** @param  Collection<int, CartItemData>  $items */
    public function __construct(
        public readonly ?int $id,
        public readonly Collection $items,
        public readonly int $count,
        public readonly int $totalCents,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
}
