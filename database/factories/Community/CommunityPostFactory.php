<?php

namespace Database\Factories\Community;

use App\Models\Community\CommunityPost;
use App\Services\CommunityService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityPost>
 */
class CommunityPostFactory extends Factory
{
    protected $model = CommunityPost::class;

    public function definition(): array
    {
        // Locale it_IT: nomi e testi restano coerenti con la UI italiana.
        return [
            'user_id' => null,
            'author_name' => fake('it_IT')->firstName(),
            'title' => ucfirst(fake('it_IT')->words(4, true)),
            'tag' => fake()->randomElement(CommunityService::TAGS),
            'body' => fake('it_IT')->paragraph(5),
        ];
    }
}
