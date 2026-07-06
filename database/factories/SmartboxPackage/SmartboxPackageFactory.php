<?php

namespace Database\Factories\SmartboxPackage;

use App\Enums\ProductType;
use App\Models\SmartboxPackage\SmartboxPackage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SmartboxPackage>
 */
class SmartboxPackageFactory extends Factory
{
    protected $model = SmartboxPackage::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'type' => fake()->randomElement([ProductType::Stay, ProductType::Wellness, ProductType::Adventure]),
            'title' => $title,
            'slug' => Str::slug($title),
            'audience' => fake()->randomElement(['Coppia', 'Famiglia', 'Gruppo (+5 persone)']),
            'audience_people' => 2,
            'price_cents' => fake()->numberBetween(100, 500) * 100,
            'price_from_cents' => 0,
            'validity_months' => 12,
            'img' => 'smartbox-relax-lombardia',
            'hero_img' => 'smartbox-dettaglio-hero',
            'description' => fake()->paragraph(),
            'extended_description' => fake()->paragraph(),
            'general_info' => [],
            'features' => [],
            'position' => fake()->unique()->numberBetween(1, 500),
        ];
    }
}
