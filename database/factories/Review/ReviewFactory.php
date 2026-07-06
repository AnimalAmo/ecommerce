<?php

namespace Database\Factories\Review;

use App\Models\Review\Review;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'reviewable_type' => 'structure',
            'reviewable_id' => Structure::factory(),
            'author_name' => $name,
            'author_initials' => Str::of($name)->explode(' ')->map(fn (string $part) => mb_substr($part, 0, 1))->take(2)->implode(''),
            'avatar_color' => fake()->hexColor(),
            'rating' => fake()->randomElement([3.0, 3.5, 4.0, 4.5, 5.0]),
            'title' => fake()->sentence(2),
            'body' => fake()->paragraph(),
            'reviewed_at' => fake()->date(),
            'position' => fake()->numberBetween(1, 20),
        ];
    }
}
