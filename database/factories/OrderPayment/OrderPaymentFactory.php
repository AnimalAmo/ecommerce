<?php

namespace Database\Factories\OrderPayment;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order\Order;
use App\Models\OrderPayment\OrderPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OrderPayment>
 */
class OrderPaymentFactory extends Factory
{
    protected $model = OrderPayment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => PaymentMethod::Card,
            'status' => PaymentStatus::Pending,
            'amount_cents' => fake()->numberBetween(20, 500) * 100,
            'transaction_id' => null,
            'gateway_session_id' => 'pi_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'provider' => 'stripe',
            'provider_response' => null,
            'paid_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Completed,
            'transaction_id' => $attributes['gateway_session_id'],
            'provider_response' => ['status' => 'succeeded'],
            'paid_at' => Carbon::now(),
        ]);
    }
}
