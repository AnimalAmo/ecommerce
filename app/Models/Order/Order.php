<?php

namespace App\Models\Order;

use App\Enums\OrderStatus;
use App\Models\Order\Concerns\OrderHasBootAttributes;
use App\Models\Order\Concerns\OrderHasRelationships;
use Database\Factories\Order\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Testata ordine: snapshot buyer + totale in cents. user_id nullable
 * (guest checkout permesso). order_number generato in creating.
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, OrderHasBootAttributes, OrderHasRelationships;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'is_gift',
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'is_gift' => 'boolean',
            'total_cents' => 'integer',
        ];
    }
}
