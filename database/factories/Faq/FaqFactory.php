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

    /** FAQ della pagina di assistenza: nessuna scheda, un argomento. */
    public function platform(string $topic = 'bookings'): static
    {
        return $this->state(fn (): array => [
            'faqable_type' => null,
            'faqable_id' => null,
            'topic' => $topic,
        ]);
    }
}
