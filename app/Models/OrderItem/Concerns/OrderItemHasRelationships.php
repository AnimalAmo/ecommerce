<?php

namespace App\Models\OrderItem\Concerns;

use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait OrderItemHasRelationships
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Morph nullable verso il catalogo (alias morph map: structure/event/smartbox_package). */
    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }
}
