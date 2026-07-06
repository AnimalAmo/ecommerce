<?php

namespace Database\Factories\Venue;

use App\Models\Venue\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->address(),
            'map_img' => 'event-detail-map',
        ];
    }
}
