<?php

namespace Tests\Feature\Cart;

use App\Models\Event\Event;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureClosure;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StructureClosureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Seed dello step 3 carrello: chiusure demo delle strutture (idempotenti,
 * relative a oggi) e capienza massima degli eventi.
 */
class CartSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_structure_closure_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 7 chiusure demo: 3 (Hotel Brescia) + 1 (Tre Capitelli) + 1 (Dog sitting) + 2 (Lamasu).
        $this->assertSame(7, StructureClosure::count());

        // Secondo run nello stesso giorno: updateOrCreate su [structure_id, date] non duplica.
        $this->seed(StructureClosureSeeder::class);

        $this->assertSame(7, StructureClosure::count());
    }

    public function test_seeded_closures_cover_the_known_structures(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Hotel Brescia (prima card): range demo di 3 giorni relativo a oggi.
        $hotel = Structure::where('position', 1)->firstOrFail();

        foreach ([3, 4, 5] as $offset) {
            $this->assertDatabaseHas('structure_closures', [
                'structure_id' => $hotel->id,
                'date' => Carbon::today()->addDays($offset)->toDateString(),
            ]);
        }

        // Dog sitting: il giorno chiuso coincide col default del widget (demo del picker).
        $service = Structure::where('position', 4)->firstOrFail();

        $this->assertDatabaseHas('structure_closures', [
            'structure_id' => $service->id,
            'date' => Carbon::today()->addDays(7)->toDateString(),
        ]);

        // Integrità referenziale: ogni chiusura risolve la sua struttura.
        StructureClosure::with('structure')->get()
            ->each(fn (StructureClosure $closure) => $this->assertNotNull($closure->structure));
    }

    public function test_seeded_events_have_max_participants(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Capienza demo: default 30, override 8 (evento piccolo) e 20 (attività escursioni).
        $this->assertSame(8, Event::where('slug', 'brunch-pet-friendly')->firstOrFail()->max_participants);
        $this->assertSame(20, Event::where('slug', 'weekend-escursioni')->firstOrFail()->max_participants);
        $this->assertSame(30, Event::where('slug', 'festa-pet-friendly')->firstOrFail()->max_participants);

        // Nessun evento seed senza capienza (null = illimitato resta un caso solo da factory).
        $this->assertSame(0, Event::whereNull('max_participants')->count());
    }
}
