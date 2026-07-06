<?php

namespace Database\Factories\Faq;

use App\Models\Faq\Faq;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'faqable_type' => 'structure',
            'faqable_id' => Structure::factory(),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'position' => fake()->numberBetween(1, 10),
        ];
    }
}
