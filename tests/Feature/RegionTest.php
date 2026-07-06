<?php

namespace Tests\Feature;

use App\Models\Region\Region;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_twenty_italian_regions(): void
    {
        $this->seed(RegionSeeder::class);

        $this->assertSame(20, Region::count());
        $this->assertSame('Lombardia', Region::where('slug', 'lombardia')->value('name'));
        $this->assertSame('Valle d’Aosta', Region::where('slug', 'valle-daosta')->value('name'));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RegionSeeder::class);
        $this->seed(RegionSeeder::class);

        $this->assertSame(20, Region::count());
    }

    public function test_route_key_is_the_slug(): void
    {
        $region = Region::factory()->create(['slug' => 'lombardia']);

        $this->assertSame('lombardia', $region->getRouteKeyName() === 'slug' ? $region->slug : null);
        $this->assertTrue($region->is(Region::where('slug', 'lombardia')->first()));
    }
}
