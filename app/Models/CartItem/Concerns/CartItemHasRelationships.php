<?php

namespace App\Models\CartItem\Concerns;

use App\Models\Cart\Cart;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait CartItemHasRelationships
{
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /** Prodotto acquistabile: structure/event/smartbox_package (morph map). */
    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }
}
