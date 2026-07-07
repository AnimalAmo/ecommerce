<?php

namespace Database\Factories\OrderItem;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $checkIn = Carbon::today()->addDays(7);
        $checkOut = Carbon::today()->addDays(12);

        return [
            'order_id' => Order::factory(),
            'purchasable_type' => 'structure',
            'purchasable_id' => Structure::factory(),
            'title' => fake()->company(),
            'photo_url' => '/img/room-hotel-baubau.jpg',
            'product_type' => ProductType::Structure,
            'location' => fake()->city().', Italia',
            'price_cents' => fake()->numberBetween(20, 300) * 100,
            'is_gift' => false,
            // Vocabolario canonico famiglia structure (chiavi ksortate, come nel carrello).
            'options' => [
                'animals' => ['cane' => 1],
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
            ],
            'booked_from' => $checkIn,
            'booked_until' => $checkOut,
        ];
    }

    public function forEvent(): static
    {
        $startsAt = Carbon::today()->addDays(10)->setTime(18, 0);

        return $this->state(fn (): array => [
            'purchasable_type' => 'event',
            'purchasable_id' => Event::factory(),
            'title' => fake()->sentence(3),
            'product_type' => ProductType::Event,
            'options' => ['participants' => 1],
            'booked_from' => $startsAt,
            'booked_until' => $startsAt->copy()->addHours(2),
        ]);
    }

    public function forSmartbox(): static
    {
        return $this->state(fn (): array => [
            'purchasable_type' => 'smartbox_package',
            'purchasable_id' => SmartboxPackage::factory(),
            'title' => 'Smartbox '.fake()->word(),
            'product_type' => ProductType::Stay,
            'location' => null,
            'options' => ['animals' => ['cane' => 1]],
            'booked_from' => Carbon::now(),
            'booked_until' => Carbon::now()->addMonths(12),
        ]);
    }

    /** Riga regalo (smartbox): options.gift con dedica/messaggio/destinatario. */
    public function gift(): static
    {
        return $this->forSmartbox()->state(fn (): array => [
            'is_gift' => true,
            'options' => [
                'animals' => ['cane' => 1],
                'gift' => [
                    'dedication' => fake()->name(),
                    'message' => fake()->sentence(),
                    'recipient_email' => fake()->safeEmail(),
                ],
            ],
        ]);
    }
}
