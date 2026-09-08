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
        // Dati di PIATTAFORMA: senza questi l'app non funziona, quindi girano
        // sempre, produzione compresa. Nessuno di loro pubblica un'offerta.
        $this->call([
            RegionSeeder::class,
            ProvinceSeeder::class, // dopo le regioni: assegna region_id

            // Tassonomia dei servizi: la scelgono i partner in fase di
            // pubblicazione, e i seeder del catalogo mock ci si agganciano.
            AmenitySeeder::class,
            PaymentGatewaySeeder::class,
            // Prima dei ruoli non si registra nessuno: la registrazione chiama
            // assignRole('client') e senza la riga esplode con RoleDoesNotExist.
            RoleSeeder::class,
            PageSeeder::class, // contenuto istituzionale: serve anche in produzione
            ArticleSeeder::class, // Animal Times: articoli della cliente, servono anche in produzione
        ]);

        // Oltre questa riga si semina solo roba FINTA. Il catalogo (12 strutture,
        // 17 eventi, 12 cofanetti) è copiato verbatim dal mock XD, recensioni
        // inventate comprese: su animalamo.it sarebbero offerte acquistabili di
        // strutture che non esistono e prezzi che nessun partner ha deciso.
        // Le persone del mock portano poi password note e il proprio nome nei
        // flussi che le toccano (il prefill di "Lavora con noi" finisce nella
        // mail di invito). Fuori da locale/test si semina solo su richiesta
        // esplicita: vedi config/app.php → seed_demo_data.
        if (! config('app.seed_demo_data')) {
            return;
        }

        $this->call([
            StructureSeeder::class,
            StructureClosureSeeder::class, // dopo le strutture: le cerca per position
            EventSeeder::class,
            SmartboxPackageSeeder::class,

            DemoUserSeeder::class, // dopo il catalogo: i preferiti puntano lì
            DemoOrderSeeder::class,
            CommunitySeeder::class, // dopo gli utenti demo: "I miei post" è di Giulia
        ]);
    }
}
