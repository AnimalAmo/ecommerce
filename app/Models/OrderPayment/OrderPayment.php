<?php

namespace App\Models\OrderPayment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\OrderPayment\Concerns\OrderPaymentHasRelationships;
use App\Observers\OrderPaymentObserver;
use Database\Factories\OrderPayment\OrderPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pagamento dell'ordine (uno per ordine in step 4). L'observer emette
 * OrderPaid alla transizione a Completed (idempotente).
 */
#[ObservedBy(OrderPaymentObserver::class)]
class OrderPayment extends Model
{
    /** @use HasFactory<OrderPaymentFactory> */
    use HasFactory, OrderPaymentHasRelationships;

    protected $fillable = [
        'order_id',
        'payment_method',
        'status',
        'amount_cents',
        'transaction_id',
        'gateway_session_id',
        'provider',
        'provider_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'provider_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }
}
