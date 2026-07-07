<?php

namespace App\Models\OrderPayment\Concerns;

use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OrderPaymentHasRelationships
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
