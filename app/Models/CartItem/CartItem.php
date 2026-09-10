<?php

namespace App\Models\CartItem;

use App\Models\CartItem\Concerns\CartItemHasRelationships;
use Database\Factories\CartItem\CartItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Riga carrello: quantità fissa 1, prezzo snapshot in cents riprezzato server-side.
 * Il dedup (purchasable + options canonicalizzate + is_gift) è applicativo, non a db.
 */
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use CartItemHasRelationships, HasFactory;

    protected $fillable = [
        'cart_id',
        'purchasable_type',
        'purchasable_id',
        'partner_user_id',
        'is_gift',
        'price_cents',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'is_gift' => 'boolean',
            'price_cents' => 'integer',
            'options' => 'array',
        ];
    }
}
