<?php

namespace App\Models\Order;

use App\Enums\OrderStatus;
use App\Models\Order\Concerns\OrderHasBootAttributes;
use App\Models\Order\Concerns\OrderHasRelationships;
use App\Support\Phone;
use Database\Factories\Order\OrderFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /** Snapshot buyer in formato canonico: il telefono si salva sempre in E.164. */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => Phone::toE164($value));
    }
}
