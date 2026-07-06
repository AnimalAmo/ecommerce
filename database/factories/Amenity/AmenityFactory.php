<?php

namespace Database\Factories\Amenity;

use App\Models\Amenity\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amenity>
 */
class AmenityFactory extends Factory
{
    protected $model = Amenity::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'group' => fake()->randomElement([Amenity::GROUP_HOTEL, Amenity::GROUP_ANIMAL]),
        ];
    }
}
