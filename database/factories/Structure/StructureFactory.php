<?php

namespace Database\Factories\Structure;

use App\Enums\ProductType;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Structure>
 */
class StructureFactory extends Factory
{
    protected $model = Structure::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'type' => ProductType::Structure,
            'name' => $name,
            'slug' => Str::slug($name),
            'location' => fake()->city().', Italia',
            'rating' => fake()->randomElement([3.0, 3.5, 4.0, 4.5, 5.0]),
            'price_cents' => fake()->numberBetween(20, 300) * 100,
            'price_from_cents' => 0,
            'img' => 'regione-hotel-brescia',
            'hero_img' => 'struttura-hero',
            'map_img' => 'struttura-mappa',
            'description' => fake()->paragraph(),
            'general_info' => [],
            'features' => [],
            'position' => fake()->unique()->numberBetween(1, 500),
        ];
    }

    public function service(): static
    {
        return $this->state(fn (): array => [
            'type' => ProductType::Service,
            'hero_img' => 'servizio-hero',
            'map_img' => 'servizio-mappa',
            'features' => null,
        ]);
    }
}
