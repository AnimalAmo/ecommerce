<?php

namespace Tests\Feature;

use App\Livewire\Catalog\Events;
use App\Models\Event\Event;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * I DB seminati prima della conversione translatable (demo) hanno ancora testo
 * piano nelle colonne titolo/descrizione: json_extract() esplode alla prima
 * ricerca "Dove" (SQLSTATE 22032 su MySQL, "malformed JSON" su sqlite).
 * La migration di normalizzazione avvolge i valori legacy in {"it": …}.
 */
class NormalizeLegacyTranslatableTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_07_24_120001_normalize_legacy_translatable_columns.php';

    protected function setUp(): void
    {
        parent::setUp();

        // I contenuti legacy sono italiani: la ricerca va sul path JSON del locale corrente.
        app()->setLocale('it');
    }

    /** Riproduzione del bug demo: titolo pre-translatable → la ricerca "Dove" va in QueryException. */
    public function test_plain_string_title_breaks_the_dove_search(): void
    {
        $this->insertLegacyEvent();

        $this->expectException(QueryException::class);

        Livewire::test(Events::class)->set('where', 'trento');
    }

    public function test_migration_wraps_legacy_plain_strings_into_it_json(): void
    {
        $eventId = $this->insertLegacyEvent();
        $boxId = $this->insertLegacySmartbox();
        $structureId = $this->insertLegacyStructure();
        $draftId = DB::table('structure_drafts')->insertGetId(['name' => 'Agriturismo legacy']);

        $this->runNormalizationMigration();

        $this->assertSame(['it' => 'Weekend a Trento LEGACY'], json_decode(DB::table('events')->where('id', $eventId)->value('title'), true));
        $this->assertSame(['it' => 'Cofanetto legacy'], json_decode(DB::table('smartbox_packages')->where('id', $boxId)->value('title'), true));
        $this->assertSame(['it' => 'Hotel legacy'], json_decode(DB::table('structures')->where('id', $structureId)->value('name'), true));
        $this->assertSame(['it' => 'Agriturismo legacy'], json_decode(DB::table('structure_drafts')->where('id', $draftId)->value('name'), true));

        // Spatie ora legge il titolo come traduzione italiana.
        $this->assertSame('Weekend a Trento LEGACY', Event::query()->find($eventId)->title);
    }

    public function test_dove_search_works_on_normalized_legacy_rows(): void
    {
        $this->insertLegacyEvent();

        $this->runNormalizationMigration();

        Livewire::test(Events::class)
            ->set('where', 'trento')
            ->assertSee('Weekend a Trento LEGACY');
    }

    public function test_migration_leaves_valid_json_rows_untouched(): void
    {
        // Riga già nel formato spatie (il factory passa dal cast translatable).
        $event = Event::factory()->create(['title' => 'Già convertito', 'description' => 'Descrizione ok']);
        $before = DB::table('events')->where('id', $event->id)->first();

        $this->runNormalizationMigration();

        $after = DB::table('events')->where('id', $event->id)->first();
        $this->assertSame($before->title, $after->title);
        $this->assertSame($before->description, $after->description);
        $this->assertSame('Già convertito', $event->fresh()->title);
    }

    private function runNormalizationMigration(): void
    {
        (require base_path(self::MIGRATION))->up();
    }

    /** Insert grezzi via query builder: aggirano i cast del model, come i seed pre-conversione. */
    private function insertLegacyEvent(): int
    {
        return DB::table('events')->insertGetId([
            'type' => 'event',
            'title' => 'Weekend a Trento LEGACY',
            'slug' => 'weekend-a-trento-legacy',
            'location' => 'Altrove, Italia',
            'description' => 'Descrizione in testo piano.',
            'img' => 'event-brunch-pet-friendly',
        ]);
    }

    private function insertLegacySmartbox(): int
    {
        return DB::table('smartbox_packages')->insertGetId([
            'type' => 'stay',
            'title' => 'Cofanetto legacy',
            'slug' => 'cofanetto-legacy',
            'audience' => 'Coppia',
            'price_cents' => 10000,
            'img' => 'smartbox-img',
            'hero_img' => 'smartbox-hero',
            'description' => 'Descrizione piana.',
            'extended_description' => 'Descrizione estesa piana.',
            'general_info' => '[]',
            'features' => '[]',
            'position' => 1,
        ]);
    }

    private function insertLegacyStructure(): int
    {
        return DB::table('structures')->insertGetId([
            'type' => 'structure',
            'name' => 'Hotel legacy',
            'slug' => 'hotel-legacy',
            'location' => 'Milano, Italia',
            'rating' => 4.5,
            'price_cents' => 20000,
            'img' => 'structure-img',
            'hero_img' => 'structure-hero',
            'map_img' => 'structure-map',
            'description' => 'Descrizione piana.',
            'general_info' => '[]',
            'position' => 1,
        ]);
    }
}
