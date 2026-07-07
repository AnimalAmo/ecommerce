<?php

namespace Database\Factories\Structure;

use App\Models\Structure\Structure;
use App\Models\Structure\StructureClosure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<StructureClosure>
 */
class StructureClosureFactory extends Factory
{
    protected $model = StructureClosure::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'date' => Carbon::today()->addDays(fake()->unique()->numberBetween(1, 365))->toDateString(),
        ];
    }
}
