<?php

namespace Database\Factories\Pet;

use App\Models\Pet\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pet>
 */
class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'species' => fake()->randomElement(['Cane', 'Gatto', 'Coniglio']),
            'name' => fake()->firstName(),
            'size' => fake()->randomElement(['Piccola', 'Media', 'Grande']),
        ];
    }
}
