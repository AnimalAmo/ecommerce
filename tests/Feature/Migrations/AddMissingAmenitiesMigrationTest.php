<?php

namespace Tests\Feature\Migrations;

use App\Models\Amenity\Amenity;
use App\Models\Structure\Structure;
use App\Services\Partner\Publishing\FamilyPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * WP8, 28/09/2026: sei slug del wizard (riscaldamento, ricarica_elettrica,
 * tv, piscina, area_animali e il campo_da_tennis della smartbox) non avevano
 * una voce a catalogo. Il seeder ora le
 * ha, ma al deploy non gira: la migrazione 2026_09_28_140001 le porta nel
 * database del cliente, che ha le quattordici voci della prima semina.
 *
 * Il database «del cliente» si ricostruisce qui a mano, con le quattordici
 * voci di allora nell'ordine di allora: sono un fatto storico, non si leggono
 * dal seeder di oggi.
 */
class AddMissingAmenitiesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_28_140001_add_missing_amenities.php';

    /** Il catalogo fino al 27/09/2026 (AmenitySeeder di allora), in ordine di id. */
    private const CATALOG_BEFORE = [
        Amenity::GROUP_HOTEL => ['Aria condizionata negli spazi comuni', 'Lavanderia', 'Ascensore', 'Wifi', 'Noleggio bici', 'Spa', 'Pranzo'],
        Amenity::GROUP_ANIMAL => ['Pet sitting', 'Dog sitter', 'Servizio veterinario', 'Omaggio di benvenuto', 'Dog Beach nelle vicinanze', 'Supplemento animali', 'Piscina per cani'],
    ];

    /** Le sei voci che la migrazione aggiunge, con il loro gruppo. */
    private const ADDED = [
        'Riscaldamento' => Amenity::GROUP_HOTEL,
        'Ricarica auto elettriche' => Amenity::GROUP_HOTEL,
        'TV' => Amenity::GROUP_HOTEL,
        'Piscina' => Amenity::GROUP_HOTEL,
        'Campo da tennis' => Amenity::GROUP_HOTEL,
        'Area dedicata agli animali' => Amenity::GROUP_ANIMAL,
    ];

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    /** @return array<string, int> nome => id delle quattordici voci di allora */
    private function catalogBefore(): array
    {
        foreach (self::CATALOG_BEFORE as $group => $names) {
            foreach ($names as $name) {
                Amenity::create(['name' => $name, 'group' => $group]);
            }
        }

        return Amenity::query()->pluck('id', 'name')->all();
    }

    /** @return array<string, string> nome => gruppo */
    private function catalogNow(): array
    {
        return Amenity::query()->orderBy('name')->pluck('group', 'name')->all();
    }

    public function test_up_adds_the_five_rows_to_the_catalog_of_the_client(): void
    {
        $before = $this->catalogBefore();

        $this->migration()->up();

        $this->assertSame(20, Amenity::count());

        foreach (self::ADDED as $name => $group) {
            $this->assertSame($group, Amenity::query()->where('name', $name)->value('group'), "{$name} nel gruppo sbagliato o assente");
        }

        // Le quattordici di prima non cambiano: stesso id (il pivot delle
        // schede pubblicate punta lì), stesso gruppo.
        foreach (self::CATALOG_BEFORE as $group => $names) {
            foreach ($names as $name) {
                $this->assertSame($before[$name], Amenity::query()->where('name', $name)->value('id'));
                $this->assertSame($group, Amenity::query()->where('name', $name)->value('group'));
            }
        }
    }

    public function test_up_run_twice_does_not_duplicate(): void
    {
        $this->catalogBefore();

        $this->migration()->up();
        $once = $this->catalogNow();
        $this->migration()->up();

        $this->assertSame(20, Amenity::count());
        $this->assertSame($once, $this->catalogNow());
    }

    /**
     * Un catalogo vuoto (istanza nuova, suite dei test) è del seeder: la
     * migrazione non lo riempie.
     */
    public function test_up_leaves_an_empty_catalog_to_the_seeder(): void
    {
        $this->migration()->up();

        $this->assertSame(0, Amenity::count());
    }

    /**
     * Le due strade arrivano allo stesso catalogo: migrazione sul database
     * del cliente, seeder su un database nuovo. Se un giorno il seeder cambia
     * e la migrazione no (o il contrario), qui si vede.
     */
    public function test_the_migration_and_the_seeder_end_on_the_same_catalog(): void
    {
        $expected = [];

        foreach (AmenitySeeder::AMENITIES as $group => $names) {
            foreach ($names as $name) {
                $expected[$name] = $group;
            }
        }
        ksort($expected);

        $this->catalogBefore();
        $this->migration()->up();
        $this->assertSame($expected, $this->catalogNow(), 'migrazione sul catalogo del cliente');

        // Il seeder dopo la migrazione non trova niente da aggiungere.
        $this->seed(AmenitySeeder::class);
        $this->assertSame($expected, $this->catalogNow());
        $this->assertSame(20, Amenity::count());

        // E su un database nuovo il seeder da solo dà lo stesso catalogo, che
        // la migrazione poi non tocca.
        DB::table('amenities')->delete();
        $this->seed(AmenitySeeder::class);
        $this->migration()->up();
        $this->assertSame($expected, $this->catalogNow(), 'seeder su un database nuovo');
    }

    /**
     * Sul database del cliente le voci nuove hanno gli id più alti. La scheda
     * le deve mostrare al loro posto (quello di AmenitySeeder::AMENITIES), non
     * in coda: FamilyPublisher::amenityPivot() ordina sulla lista, non sull'id.
     */
    public function test_the_rows_added_later_take_their_place_on_the_sheet(): void
    {
        $this->catalogBefore();
        $this->migration()->up();

        $structure = Structure::factory()->create();
        $structure->amenities()->sync(FamilyPublisher::amenityPivot(array_keys(FamilyPublisher::AMENITY_MAP)));

        $this->assertSame(AmenitySeeder::AMENITIES[Amenity::GROUP_HOTEL], array_column($structure->fresh()->amenityRows(Amenity::GROUP_HOTEL), 'label'));
        $this->assertSame(AmenitySeeder::AMENITIES[Amenity::GROUP_ANIMAL], array_column($structure->fresh()->amenityRows(Amenity::GROUP_ANIMAL), 'label'));

        // Le posizioni ripartono da 1 in ogni gruppo.
        $positions = DB::table('amenityables')
            ->join('amenities', 'amenities.id', '=', 'amenityables.amenity_id')
            ->where('amenityable_id', $structure->id)
            ->where('name', 'Riscaldamento')
            ->value('position');
        $this->assertSame(2, (int) $positions);
    }

    /**
     * down() toglie le cinque voci che nessuna scheda mostra, con le loro
     * righe di pivot (quasi tutte included=false dopo una ripubblicazione),
     * e tiene quella che una scheda mostra, dicendolo nel log. Le sette della
     * cliente c'erano già e non si toccano.
     */
    public function test_down_removes_the_rows_nobody_shows_and_keeps_the_one_a_sheet_shows(): void
    {
        Log::spy();
        $this->catalogBefore();
        $this->migration()->up();

        $shown = Structure::factory()->create();
        $shown->amenities()->sync(FamilyPublisher::amenityPivot(['tv', 'lavanderia']));
        $notShown = Structure::factory()->create();
        $notShown->amenities()->sync(FamilyPublisher::amenityPivot(['wifi']));

        $this->migration()->down();

        $this->assertSame(15, Amenity::count());
        $this->assertTrue(Amenity::query()->where('name', 'TV')->exists(), 'TV la mostra una scheda: deve restare');

        foreach (['Riscaldamento', 'Ricarica auto elettriche', 'Piscina', 'Campo da tennis', 'Area dedicata agli animali'] as $name) {
            $this->assertFalse(Amenity::query()->where('name', $name)->exists(), "{$name} doveva sparire");
        }

        foreach (self::CATALOG_BEFORE as $names) {
            foreach ($names as $name) {
                $this->assertTrue(Amenity::query()->where('name', $name)->exists(), "{$name} non è della migrazione");
            }
        }

        // Nessuna riga di pivot orfana, e le schede mostrano quello di prima.
        $this->assertSame(0, DB::table('amenityables')->whereNotIn('amenity_id', Amenity::query()->pluck('id'))->count());
        $this->assertSame(['Lavanderia', 'TV'], array_column($shown->fresh()->amenityRows(Amenity::GROUP_HOTEL), 'label'));
        $this->assertSame(['Wifi'], array_column($notShown->fresh()->amenityRows(Amenity::GROUP_HOTEL), 'label'));

        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context === ['names' => ['TV']]);
    }
}
