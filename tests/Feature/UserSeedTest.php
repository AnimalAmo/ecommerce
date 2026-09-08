<?php

namespace Tests\Feature;

use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_roles_and_demo_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['client', 'partner', 'superadmin'], Role::orderBy('name')->pluck('name')->all());

        $giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();
        $this->assertSame('Giulia Rossi', $giulia->name);
        $this->assertSame('1998-03-22', $giulia->birth_date->toDateString());
        $this->assertTrue($giulia->hasRole('client'));
        $this->assertSame('Cane', $giulia->pets()->sole()->species);

        $partner = User::where('email', 'partner@animalamo.test')->firstOrFail();
        $this->assertTrue($partner->hasRole('partner'));
    }

    public function test_demo_favorites_reference_existing_catalog_rows(): void
    {
        $this->seed(DatabaseSeeder::class);

        $giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();

        $this->assertSame(6, $giulia->favorites()->count());

        // Ogni preferito risolve una riga reale del catalogo via morph map.
        $giulia->favorites->each(
            fn (Favorite $favorite) => $this->assertNotNull($favorite->favoritable)
        );

        // La card XD "Puppy Yoga, Milano" è l'evento reale puppy-yoga-milano.
        $puppyYoga = Event::where('slug', 'puppy-yoga-milano')->firstOrFail();
        $this->assertTrue(
            $giulia->favorites()
                ->where('favoritable_type', 'event')
                ->where('favoritable_id', $puppyYoga->id)
                ->exists()
        );
    }

    /**
     * Le persone del mock XD (Susanna Rossi, Giulia Rossi) hanno password
     * "password" e ruoli attivi: su un server pubblico sono un account
     * partner ad accesso noto, e il loro nome finisce nelle mail di chi prova
     * il flusso. Dal go-live su animalamo.it lo stesso vale per il catalogo
     * XD — strutture ed eventi inventati sarebbero offerte acquistabili —
     * quindi il flag spegne entrambi. Restano solo i dati di piattaforma.
     */
    public function test_demo_personas_and_mock_catalog_are_skipped_when_the_flag_is_off(): void
    {
        config(['app.seed_demo_data' => false]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Event::count());

        // I ruoli restano: senza, la registrazione esplode con RoleDoesNotExist.
        $this->assertSame(3, Role::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, User::count());
        $this->assertSame(3, Role::count());

        $giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();
        $this->assertSame(6, $giulia->favorites()->count());
        $this->assertSame(1, $giulia->pets()->count());
        $this->assertCount(1, $giulia->roles);
    }
}
