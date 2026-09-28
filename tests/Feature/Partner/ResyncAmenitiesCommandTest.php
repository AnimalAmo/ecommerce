<?php

namespace Tests\Feature\Partner;

use App\Models\Amenity\Amenity;
use App\Models\Event\Event;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * `animalamo:resync-amenities` (WP8, 28/09/2026). Il pivot amenityables lo
 * scrive il publisher alla pubblicazione: senza il comando una scheda
 * pubblicata prima di WP8, con «TV» o «Lavanderia» spuntate in bozza, non le
 * mostrerebbe finché il partner non ripubblica.
 *
 * Il «prima» si ricostruisce com'era davvero: il catalogo di quattordici voci
 * e il pivot scritto dal publisher di allora, con la sua mappa di otto slug e
 * le posizioni per id. Poi la migrazione, poi il comando.
 */
class ResyncAmenitiesCommandTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_28_140001_add_missing_amenities.php';

    /** Il catalogo fino al 27/09/2026, in ordine di id. */
    private const CATALOG_BEFORE = [
        Amenity::GROUP_HOTEL => ['Aria condizionata negli spazi comuni', 'Lavanderia', 'Ascensore', 'Wifi', 'Noleggio bici', 'Spa', 'Pranzo'],
        Amenity::GROUP_ANIMAL => ['Pet sitting', 'Dog sitter', 'Servizio veterinario', 'Omaggio di benvenuto', 'Dog Beach nelle vicinanze', 'Supplemento animali', 'Piscina per cani'],
    ];

    /** FamilyPublisher::AMENITY_MAP fino al 27/09/2026. */
    private const MAP_BEFORE = [
        'wifi' => 'Wifi',
        'aria_condizionata' => 'Aria condizionata negli spazi comuni',
        'sauna' => 'Spa',
        'spa' => 'Spa',
        'pranzo' => 'Pranzo',
        'pet_sitting' => 'Pet sitting',
        'veterinario' => 'Servizio veterinario',
        'omaggio' => 'Omaggio di benvenuto',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');

        $this->seed([RegionSeeder::class, ProvinceSeeder::class]);

        foreach (self::CATALOG_BEFORE as $group => $names) {
            foreach ($names as $name) {
                Amenity::create(['name' => $name, 'group' => $group]);
            }
        }
    }

    // ---------------------------------------------------------------------
    // Il caso per cui il comando esiste
    // ---------------------------------------------------------------------

    public function test_a_sheet_published_before_shows_what_its_draft_ticked(): void
    {
        $structure = $this->publishedBefore($this->structureDraft([
            'services' => ['wifi', 'tv', 'lavanderia'],
            'animal_services' => ['pet_sitting', 'dog_beach'],
        ]));

        // Prima: solo le voci che la mappa di allora conosceva.
        $this->assertSame(['Wifi'], $this->shown($structure, Amenity::GROUP_HOTEL));
        $this->assertSame(['Pet sitting'], $this->shown($structure, Amenity::GROUP_ANIMAL));

        $this->runMigration();
        $this->artisan('animalamo:resync-amenities')
            ->expectsOutputToContain('risincronizzate: 1 su 1')
            ->assertSuccessful();

        $this->assertSame(['Lavanderia', 'Wifi', 'TV'], $this->shown($structure, Amenity::GROUP_HOTEL));
        $this->assertSame(['Pet sitting', 'Dog Beach nelle vicinanze'], $this->shown($structure, Amenity::GROUP_ANIMAL));

        // E la pagina pubblica le stampa.
        $html = $this->get(route('holiday.structure', ['region' => 'lombardia', 'structure' => $structure->slug]))->assertOk()->getContent();
        $this->assertSame(['Lavanderia', 'Wifi', 'TV', 'Pet sitting', 'Dog Beach nelle vicinanze'], $this->rowsOnThePage($html));
    }

    /**
     * Il comando copia dai tre publisher le colonne della bozza che leggono
     * (il suo docblock lo dichiara): se un publisher le cambia e il comando no,
     * il comando scrive una scheda diversa da quella che la ripubblicazione
     * produrrebbe. Qui le due strade si confrontano, famiglia per famiglia,
     * con una spunta in ogni colonna letta.
     *
     * @return array<string, array{0: string}>
     */
    public static function families(): array
    {
        return ['struttura' => ['struttura'], 'attività' => ['attivita'], 'smartbox' => ['smartbox']];
    }

    #[DataProvider('families')]
    public function test_the_command_writes_what_a_republish_would_write(string $family): void
    {
        [$draft, $expected] = match ($family) {
            'struttura' => [$this->structureDraft([
                'services' => ['tv', 'riscaldamento'],
                'additional_services' => ['pranzo'],
                'animal_services' => ['area_animali', 'supplemento_animali'],
            ]), ['Riscaldamento', 'TV', 'Pranzo', 'Area dedicata agli animali', 'Supplemento animali']],
            'attivita' => [$this->activityDraft([
                'services' => ['ricarica_elettrica'],
                'additional_services' => ['pranzo'],
                'animal_services' => ['dog_sitter'],
            ]), ['Ricarica auto elettriche', 'Pranzo', 'Dog sitter']],
            'smartbox' => [$this->smartboxDraft([
                'included_services' => ['ascensore'],
                'additional_services' => ['piscina'],
                'meals' => ['pranzo'],
                'animal_services' => ['piscina_cani'],
            ]), ['Ascensore', 'Piscina', 'Pranzo', 'Piscina per cani']],
        };

        $row = $this->publishedBefore($draft);

        $this->runMigration();
        $this->artisan('animalamo:resync-amenities')->assertSuccessful();
        $afterCommand = $this->pivot($row);

        $this->assertSame($expected, [...$this->shown($row, Amenity::GROUP_HOTEL), ...$this->shown($row, Amenity::GROUP_ANIMAL)]);

        app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertSame($this->pivot($row), $afterCommand, 'il comando e la ripubblicazione non danno lo stesso pivot');
    }

    // ---------------------------------------------------------------------
    // Cosa il comando non fa
    // ---------------------------------------------------------------------

    public function test_dry_run_writes_nothing(): void
    {
        $structure = $this->publishedBefore($this->structureDraft(['services' => ['tv']]));
        $this->runMigration();
        $before = $this->pivot($structure);

        $this->artisan('animalamo:resync-amenities', ['--dry-run' => true])
            ->expectsOutputToContain('Simulazione')
            ->expectsOutputToContain('che cambierebbero: 1 su 1')
            ->expectsOutputToContain('TV')
            ->assertSuccessful();

        $this->assertSame($before, $this->pivot($structure));
        $this->assertSame([], $this->shown($structure, Amenity::GROUP_HOTEL));
    }

    /**
     * Si tocca solo il pivot: la scheda non torna in moderazione, non cambia
     * slug, foto o titolo, e `updated_at` resta quello (il docblock del
     * comando: «Qui si tocca solo il pivot»). Le schede nascoste (sospese, in
     * attesa) si risincronizzano come le altre, perché il publisher le
     * aggiorna tutte allo stesso modo.
     */
    public function test_it_touches_only_the_pivot_even_on_hidden_sheets(): void
    {
        $structure = $this->publishedBefore($this->structureDraft(['services' => ['tv']]));
        DB::table('structures')->where('id', $structure->id)->update([
            'approval_status' => Structure::APPROVAL_PENDING,
            'suspended_at' => now(),
        ]);
        $before = (array) DB::table('structures')->where('id', $structure->id)->first();

        $this->runMigration();
        $this->travel(3)->days();
        $this->artisan('animalamo:resync-amenities')->assertSuccessful();

        $this->assertSame($before, (array) DB::table('structures')->where('id', $structure->id)->first());
        $this->assertSame(['TV'], $this->shown($structure, Amenity::GROUP_HOTEL));
    }

    /**
     * Le schede del catalogo demo non hanno una bozza: le loro voci le ha
     * scelte a mano il seeder, e il comando non le deve azzerare.
     */
    public function test_a_row_without_a_draft_is_left_alone(): void
    {
        $demo = Structure::factory()->create(['structure_draft_id' => null]);
        $demo->amenities()->sync([
            Amenity::query()->where('name', 'Noleggio bici')->value('id') => ['included' => true, 'position' => 1],
            Amenity::query()->where('name', 'Dog sitter')->value('id') => ['included' => true, 'position' => 1],
        ]);
        $this->runMigration();
        $before = $this->pivot($demo);

        $this->artisan('animalamo:resync-amenities')
            ->expectsOutputToContain('Nessuna scheda da cambiare')
            ->assertSuccessful();

        $this->assertSame($before, $this->pivot($demo));
    }

    /**
     * Un cambio di ramo lascia la riga della famiglia di prima (ritirata se
     * ha prenotazioni): il publisher non la aggiorna più, e nemmeno il
     * comando, che la conta fra le saltate.
     */
    public function test_a_row_left_behind_by_a_branch_switch_is_skipped(): void
    {
        $draft = $this->structureDraft(['services' => ['tv', 'ascensore']]);
        $leftover = Event::factory()->activity()->create(['structure_draft_id' => $draft->id]);
        $leftover->amenities()->sync($this->pivotBefore(['wifi']));
        $this->runMigration();
        $before = $this->pivot($leftover);

        $this->artisan('animalamo:resync-amenities')
            ->expectsOutputToContain('Saltate: 1')
            ->assertSuccessful();

        $this->assertSame($before, $this->pivot($leftover));
    }

    /**
     * Senza la migrazione le voci nuove non ci sono, e il comando scriverebbe
     * un pivot senza «TV» proprio per la scheda che l'ha spuntata: si rifiuta.
     */
    public function test_it_refuses_to_run_before_the_migration(): void
    {
        $structure = $this->publishedBefore($this->structureDraft(['services' => ['tv', 'lavanderia']]));
        $before = $this->pivot($structure);

        $this->artisan('animalamo:resync-amenities')
            ->expectsOutputToContain('TV')
            ->assertFailed();

        $this->assertSame($before, $this->pivot($structure));
    }

    public function test_a_second_run_finds_nothing_to_change(): void
    {
        $structure = $this->publishedBefore($this->structureDraft(['services' => ['tv']]));
        $this->runMigration();

        $this->artisan('animalamo:resync-amenities')->assertSuccessful();
        $once = $this->pivot($structure);

        $this->artisan('animalamo:resync-amenities')
            ->expectsOutputToContain('risincronizzate: 0 su 1')
            ->expectsOutputToContain('Nessuna scheda da cambiare')
            ->assertSuccessful();

        $this->assertSame($once, $this->pivot($structure));
    }

    // ---------------------------------------------------------------------
    // Aiuti
    // ---------------------------------------------------------------------

    private function runMigration(): void
    {
        (require base_path(self::MIGRATION))->up();
    }

    /**
     * Pubblica la bozza col publisher di oggi (per avere una riga vera, con
     * slug e foto) e ne riscrive il pivot come l'avrebbe scritto il publisher
     * fino al 27/09/2026.
     */
    private function publishedBefore(StructureDraft $draft): Model
    {
        $row = app(DraftPublisher::class)->publish($draft);

        $row->amenities()->sync($this->pivotBefore([
            ...($draft->services ?? []),
            ...($draft->included_services ?? []),
            ...($draft->additional_services ?? []),
            ...($draft->meals ?? []),
            ...($draft->animal_services ?? []),
        ]));

        return $row;
    }

    /**
     * Il syncAmenities() fino al 27/09/2026: una riga per voce, posizioni per
     * id dentro il gruppo, included solo per gli slug della mappa di allora.
     *
     * @param  list<string>  $slugs
     * @return array<int, array{included: bool, position: int}>
     */
    private function pivotBefore(array $slugs): array
    {
        $names = array_values(array_intersect_key(self::MAP_BEFORE, array_flip($slugs)));
        $payload = [];

        foreach ([Amenity::GROUP_HOTEL, Amenity::GROUP_ANIMAL] as $group) {
            $position = 0;

            foreach (Amenity::query()->where('group', $group)->orderBy('id')->get() as $amenity) {
                $payload[$amenity->id] = ['included' => in_array($amenity->name, $names, true), 'position' => ++$position];
            }
        }

        return $payload;
    }

    /** @return array<int, array{included: bool, position: int}> il pivot com'è a database */
    private function pivot(Model $row): array
    {
        return DB::table('amenityables')
            ->where('amenityable_type', $row->getMorphClass())
            ->where('amenityable_id', $row->getKey())
            ->orderBy('amenity_id')
            ->get()
            ->mapWithKeys(fn (object $pivot): array => [(int) $pivot->amenity_id => ['included' => (bool) $pivot->included, 'position' => (int) $pivot->position]])
            ->all();
    }

    /** @return list<string> le voci che la scheda mostra in un gruppo, in ordine */
    private function shown(Model $row, string $group): array
    {
        return array_column($row->fresh()->amenityRows($group), 'label');
    }

    /** @return list<string> le righe «servizi» della pagina pubblica, in ordine */
    private function rowsOnThePage(string $html): array
    {
        preg_match_all('/<li\b[^>]*\bwire:key="srv-(?:hotel|animal)-\d+"[^>]*>(.*?)<\/li>/s', $html, $matches);

        return array_map(fn (string $row): string => trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($row), ENT_QUOTES))), $matches[1]);
    }

    private function structureDraft(array $attributes): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Bau Resort'],
            'province' => 'BS',
            'rooms' => [['type' => 'doppia', 'count' => 3, 'price' => '80']],
            'photos' => ['structure-photos/cover.jpg'],
        ], $attributes));
    }

    private function activityDraft(array $attributes): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'attivita',
            'name' => ['it' => 'Passeggiata a sei zampe'],
            'province' => 'MI',
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'photos' => ['structure-photos/attivita.jpg'],
        ], $attributes));
    }

    private function smartboxDraft(array $attributes): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Weekend Zen col tuo cane'],
            'duration_days' => 2,
            'price' => '215',
            'photos' => ['smartbox-photos/zen.jpg'],
        ], $attributes));
    }
}
