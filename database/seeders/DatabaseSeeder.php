<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            RegionSeeder::class,
            AmenitySeeder::class,
            StructureSeeder::class,
            StructureClosureSeeder::class,
            EventSeeder::class,
            SmartboxPackageSeeder::class,
            PaymentGatewaySeeder::class,
            RoleSeeder::class,
            DemoUserSeeder::class,
            DemoOrderSeeder::class,
        ]);
    }
}
