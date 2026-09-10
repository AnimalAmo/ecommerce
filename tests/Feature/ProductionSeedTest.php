<?php

namespace Tests\Feature;

use App\Models\Amenity\Amenity;
use App\Models\Article\Article;
use App\Models\Event\Event;
use App\Models\Page\Page;
use App\Models\PaymentGateway\PaymentGateway;
use App\Models\Region\Province;
use App\Models\Region\Region;
use App\Models\Review\Review;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureClosure;
use App\Models\User;
use App\Models\Venue\Venue;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * `php artisan db:seed` deve poter girare su animalamo.it senza pubblicare
 * niente di finto: le 12 strutture, i 17 eventi e i 12 cofanetti sono copiati
 * VERBATIM dal mock XD (recensioni inventate e chiusure comprese) e a flag
 * spento diventerebbero offerte acquistabili di strutture inesistenti.
 *
 * Le assert guardano i conteggi dei model, non l'elenco dei seeder: quello che
 * conta è cosa finisce nel database del cliente.
 */
class ProductionSeedTest extends TestCase
{
    use RefreshDatabase;

    /** In produzione il flag è spento: nessun prodotto del mock XD deve esistere. */
    public function test_production_seed_publishes_no_mock_catalogue(): void
    {
        config(['app.seed_demo_data' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Structure::count());
        $this->assertSame(0, StructureClosure::count());
        $this->assertSame(0, Event::count());
        $this->assertSame(0, SmartboxPackage::count());
        // Venue e Review nascono dentro i seeder del catalogo mock: se restano
        // righe qui, un seeder è rimasto dalla parte sbagliata del gate.
        $this->assertSame(0, Venue::count());
        $this->assertSame(0, Review::count());

        // Le persone del mock XD hanno password "password": nessun utente.
        $this->assertSame(0, User::count());
    }

    /**
     * L'altra metà del taglio: senza questi dati l'app non parte nemmeno.
     * I ruoli in particolare — la registrazione chiama assignRole('client') e
     * senza la riga esplode con RoleDoesNotExist.
     */
    public function test_production_seed_keeps_the_platform_data(): void
    {
        config(['app.seed_demo_data' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['client', 'partner', 'superadmin'], Role::orderBy('name')->pluck('name')->all());

        $this->assertSame(20, Region::count());
        $this->assertSame(107, Province::count());
        // Le province si agganciano alla regione: l'ordine dei due seeder regge.
        $this->assertSame(0, Province::whereNull('region_id')->count());

        $this->assertSame(14, Amenity::count());
        $this->assertSame(1, PaymentGateway::where('code', 'stripe')->count());

        // Contenuti della cliente, non del mock: legali + Animal Times.
        $this->assertSame(3, Page::count());
        $this->assertSame(4, Article::count());
    }

    /** Col flag acceso (locale, demo) il catalogo del mock torna: nessuna regressione. */
    public function test_demo_flag_brings_the_mock_catalogue_back(): void
    {
        config(['app.seed_demo_data' => true]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(12, Structure::count());
        $this->assertSame(17, Event::count());
        $this->assertSame(12, SmartboxPackage::count());
        $this->assertSame(7, StructureClosure::count());
        $this->assertSame(2, Venue::count());
        // Tre: Giulia, il partner demo e il partner proprietario del resto
        // del catalogo mock (senza proprietario un prodotto non è vendibile).
        $this->assertSame(3, User::count());

        // E i dati di piattaforma restano al loro posto.
        $this->assertSame(3, Role::count());
        $this->assertSame(20, Region::count());
        $this->assertSame(4, Article::count());
    }
}
