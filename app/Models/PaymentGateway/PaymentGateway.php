<?php

namespace App\Models\PaymentGateway;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro gateway ('stripe'): pilota le righe metodo visibili al
 * checkout e la registrazione delle rotte webhook. Seed: PaymentGatewaySeeder.
 */
class PaymentGateway extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
