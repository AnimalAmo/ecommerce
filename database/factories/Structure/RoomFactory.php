<?php

namespace Database\Factories\Structure;

use App\Models\Structure\Room;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'draft_key' => (string) Str::uuid(),
            'type' => fake()->randomElement(['singola', 'doppia', 'tripla', 'suite']),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'price_cents' => fake()->numberBetween(50, 300) * 100,
            'max_guests' => 2,
            'max_animals' => 1,
            'units' => 1,
            'photos' => null,
            'position' => fake()->unique()->numberBetween(1, 100000),
        ];
    }

    /** Numero di camere identiche disponibili. */
    public function units(int $units): static
    {
        return $this->state(fn (): array => ['units' => $units]);
    }
}
