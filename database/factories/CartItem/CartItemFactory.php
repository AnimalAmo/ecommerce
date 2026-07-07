<?php

namespace Database\Factories\CartItem;

use App\Models\Cart\Cart;
use App\Models\CartItem\CartItem;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'purchasable_type' => 'structure',
            'purchasable_id' => Structure::factory(),
            'is_gift' => false,
            'price_cents' => fake()->numberBetween(20, 300) * 100,
            // Vocabolario canonico famiglia structure (chiavi ksortate).
            'options' => [
                'animals' => ['cane' => 1],
                'check_in' => Carbon::today()->addDays(7)->toDateString(),
                'check_out' => Carbon::today()->addDays(12)->toDateString(),
                'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
            ],
        ];
    }

    public function forEvent(): static
    {
        return $this->state(fn (): array => [
            'purchasable_type' => 'event',
            'purchasable_id' => Event::factory(),
            'options' => ['participants' => 1],
        ]);
    }

    public function forSmartbox(): static
    {
        return $this->state(fn (): array => [
            'purchasable_type' => 'smartbox_package',
            'purchasable_id' => SmartboxPackage::factory(),
            'options' => ['animals' => ['cane' => 1]],
        ]);
    }

    /** Riga regalo (smartbox): options.gift con dedica/messaggio opzionali. */
    public function gift(): static
    {
        return $this->forSmartbox()->state(fn (): array => [
            'is_gift' => true,
            'options' => [
                'animals' => ['cane' => 1],
                'gift' => ['dedication' => null, 'message' => null, 'recipient_email' => null],
            ],
        ]);
    }
}
