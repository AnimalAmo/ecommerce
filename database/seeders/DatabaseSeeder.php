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
            RegionSeeder::class,
            ProvinceSeeder::class, // dopo le regioni: assegna region_id

            AmenitySeeder::class,
            StructureSeeder::class,
            StructureClosureSeeder::class,
            EventSeeder::class,
            SmartboxPackageSeeder::class,
            PaymentGatewaySeeder::class,
            RoleSeeder::class,
        ]);

        // Le persone del mock XD portano password note e il proprio nome nei
        // flussi che le toccano (il prefill di "Lavora con noi" finisce nella
        // mail di invito). Fuori da locale/test si seminano solo su richiesta
        // esplicita: vedi config/app.php → seed_demo_data.
        if (! config('app.seed_demo_data')) {
            return;
        }

        $this->call([
            DemoUserSeeder::class,
            DemoOrderSeeder::class,
            CommunitySeeder::class, // dopo gli utenti demo: "I miei post" è di Giulia
        ]);
    }
}
