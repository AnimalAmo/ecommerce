<?php

namespace App\Models\OrderItem;

use App\Enums\ProductType;
use App\Models\OrderItem\Concerns\OrderItemHasRelationships;
use Database\Factories\OrderItem\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Riga ordine: snapshot display autonomo dal catalogo (il purchasable morph è
 * nullable: la riga resta leggibile anche se il prodotto viene rimosso).
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, OrderItemHasRelationships;

    protected $fillable = [
        'order_id',
        'purchasable_type',
        'purchasable_id',
        'title',
        'photo_url',
        'product_type',
        'location',
        'price_cents',
        'is_gift',
        'options',
        'booked_from',
        'booked_until',
    ];

    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'price_cents' => 'integer',
            'is_gift' => 'boolean',
            'options' => 'array',
            'booked_from' => 'datetime',
            'booked_until' => 'datetime',
        ];
    }
}
