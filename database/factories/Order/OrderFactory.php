<?php

namespace Database\Factories\Order;

use App\Enums\OrderPaymentMode;
use App\Enums\OrderStatus;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            // order_number omesso: lo genera il creating hook (OrderHasBootAttributes).
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            // Esplicito: il model di create() non rilegge il default della colonna.
            'payment_mode' => OrderPaymentMode::Online,
            'is_gift' => false,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => 'Italia',
            'total_cents' => fake()->numberBetween(20, 500) * 100,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
        ]);
    }

    /** Prenotazione da pagare al partner fuori piattaforma: confermata, mai "pagata". */
    public function onSite(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Confirmed,
            'payment_mode' => OrderPaymentMode::OnSite,
            'checkout_token' => (string) Str::ulid(),
        ]);
    }

    /** Guest checkout: nessun utente collegato. */
    public function guest(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
        ]);
    }

    public function gift(): static
    {
        return $this->state(fn (): array => [
            'is_gift' => true,
        ]);
    }
}
