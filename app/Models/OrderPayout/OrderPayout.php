<?php

namespace App\Models\OrderPayout;

use App\Enums\PayoutStatus;
use App\Models\OrderPayout\Concerns\OrderPayoutHasRelationships;
use Database\Factories\OrderPayout\OrderPayoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Riga del registro dei rilasci: quanto ha incassato il partner su una riga
 * d'ordine, quanto ne trattiene AnimalAmo e da quando il netto è bonificabile.
 * partner_user_id e stripe_account_id sono snapshot: il morph di order_items è
 * nullable e il legame vivo col catalogo si perde.
 */
class OrderPayout extends Model
{
    /** @use HasFactory<OrderPayoutFactory> */
    use HasFactory, OrderPayoutHasRelationships;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'partner_user_id',
        'stripe_account_id',
        'gross_cents',
        'commission_cents',
        'net_cents',
        'commission_rate_bp',
        'status',
        'release_at',
        'stripe_payout_id',
        'released_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'gross_cents' => 'integer',
            'commission_cents' => 'integer',
            'net_cents' => 'integer',
            'commission_rate_bp' => 'integer',
            'release_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }
}
