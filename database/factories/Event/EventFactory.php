<?php

namespace Database\Factories\Event;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);
        $startsAt = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            // Ogni prodotto a catalogo ha un proprietario: senza non è vendibile.
            'user_id' => User::factory(),
            'type' => ProductType::Event,
            'title' => $title,
            'slug' => Str::slug($title),
            'location' => fake()->city().', Italia',
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
            'duration_days' => null,
            'price_cents' => fake()->numberBetween(5, 50) * 100,
            'is_free' => false,
            'img' => 'event-brunch-pet-friendly',
            'hero_img' => 'event-detail-hero',
            'description' => fake()->paragraph(),
            'time_note' => fake()->sentence(),
            'venue_note' => fake()->sentence(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (): array => [
            'price_cents' => null,
            'is_free' => true,
            'hero_img' => 'free-event-detail-hero',
        ]);
    }

    public function activity(?int $durationDays = null): static
    {
        return $this->state(fn (): array => [
            'type' => ProductType::Activity,
            'starts_at' => null,
            'ends_at' => null,
            'duration_days' => $durationDays,
            'hero_img' => 'activity-detail-hero',
        ]);
    }
}
