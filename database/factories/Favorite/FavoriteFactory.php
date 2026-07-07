<?php

namespace Database\Factories\Favorite;

use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Favorite>
 */
class FavoriteFactory extends Factory
{
    protected $model = Favorite::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'favoritable_type' => 'structure',
            'favoritable_id' => Structure::factory(),
        ];
    }

    public function forEvent(): static
    {
        return $this->state(fn (): array => [
            'favoritable_type' => 'event',
            'favoritable_id' => Event::factory(),
        ]);
    }

    public function forSmartbox(): static
    {
        return $this->state(fn (): array => [
            'favoritable_type' => 'smartbox_package',
            'favoritable_id' => SmartboxPackage::factory(),
        ]);
    }
}
