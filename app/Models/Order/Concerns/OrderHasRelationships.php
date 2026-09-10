<?php

namespace App\Models\Order\Concerns;

use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayment\OrderPayment;
use App\Models\OrderPayout\OrderPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait OrderHasRelationships
{
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Registro dei rilasci: una riga per riga d'ordine (order_payouts). */
    public function payouts(): HasMany
    {
        return $this->hasMany(OrderPayout::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(OrderPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
