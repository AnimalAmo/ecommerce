<?php

namespace App\Models\OrderPayout\Concerns;

use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OrderPayoutHasRelationships
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** Beneficiario del netto: fk esplicita, il nome della colonna non è user_id. */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }
}
