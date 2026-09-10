<?php

namespace Database\Factories\OrderPayout;

use App\Enums\PayoutStatus;
use App\Models\OrderItem\OrderItem;
use App\Models\OrderPayout\OrderPayout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OrderPayout>
 */
class OrderPayoutFactory extends Factory
{
    protected $model = OrderPayout::class;

    public function definition(): array
    {
        $grossCents = fake()->numberBetween(50, 500) * 100;
        $commissionCents = intdiv($grossCents, 10);

        return [
            'order_item_id' => OrderItem::factory(),
            // Derivato dalla riga: un payout appartiene sempre all'ordine della
            // propria riga, e due Order::factory() scollegati non lo sarebbero.
            'order_id' => fn (array $attributes): int => OrderItem::findOrFail($attributes['order_item_id'])->order_id,
            'partner_user_id' => User::factory(),
            'stripe_account_id' => 'acct_'.fake()->unique()->regexify('[A-Za-z0-9]{16}'),
            'gross_cents' => $grossCents,
            'commission_cents' => $commissionCents,
            'net_cents' => $grossCents - $commissionCents,
            'commission_rate_bp' => 1000,
            'status' => PayoutStatus::Pending,
            'release_at' => Carbon::now()->addDays(14),
            'stripe_payout_id' => null,
            'released_at' => null,
            'failed_at' => null,
            'last_error' => null,
        ];
    }

    /** Riga matura: il rilascio è già dovuto, il payout non è ancora partito. */
    public function matured(): static
    {
        return $this->state(fn (): array => [
            'release_at' => Carbon::now()->subDay(),
            'status' => PayoutStatus::Pending,
        ]);
    }

    /** Riga già bonificata. */
    public function released(): static
    {
        return $this->state(fn (): array => [
            'status' => PayoutStatus::Released,
            'stripe_payout_id' => 'po_'.fake()->unique()->regexify('[A-Za-z0-9]{16}'),
            'released_at' => Carbon::now(),
        ]);
    }
}
