<?php

namespace App\Models\OrderItem\Concerns;

use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait OrderItemHasRelationships
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Venditore congelato sulla riga (snapshot: sopravvive al prodotto cancellato). */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    /** Morph nullable verso il catalogo (alias morph map: structure/event/smartbox_package). */
    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }
}
