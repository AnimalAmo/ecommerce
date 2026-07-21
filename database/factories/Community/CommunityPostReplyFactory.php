<?php

namespace Database\Factories\Community;

use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityPostReply>
 */
class CommunityPostReplyFactory extends Factory
{
    protected $model = CommunityPostReply::class;

    public function definition(): array
    {
        return [
            'community_post_id' => CommunityPost::factory(),
            'user_id' => null,
            'author_name' => fake('it_IT')->firstName(),
            'body' => fake('it_IT')->paragraph(3),
        ];
    }
}
