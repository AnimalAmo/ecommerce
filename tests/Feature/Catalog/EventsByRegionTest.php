<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Admin\Catalog\CatalogShow;
use App\Livewire\Catalog\AnimalHolidayRegion;
use App\Livewire\Catalog\Events;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Attività ed eventi per regione (cliente, 01/10/2026, forma A: barra in cima
 * a /eventi) ed eventi finiti nascosti (05/10/2026). La regione nasce dalla
 * provincia della bozza, come per le strutture.
 */
class EventsByRegionTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_05_120001_add_region_id_to_events_table.php';

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->seed([RegionSeeder::class, ProvinceSeeder::class, AmenitySeeder::class]);
    }

    private function region(string $slug): Region
    {
        return Region::query()->where('slug', $slug)->sole();
    }

    private function activityDraft(string $province, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => 'attivita',
            'name' => ['it' => 'Toelettatura Bau'],
            'description' => ['it' => 'Toelettatura a domicilio.'],
            'address' => 'Via Roma 1',
            'city' => 'Città',
            'province' => $province,
            'zip' => '20121',
            'price_type' => 'pagamento',
            'price_per_person' => '25',
            'cancellation_when' => '1',
            'photos' => ['structure-photos/a1.jpg'],
        ], $attributes));
    }

    // ── Base dati ─────────────────────────────────────────────────────────────

    public function test_the_publisher_takes_the_region_from_the_province(): void
    {
        $draft = $this->activityDraft('FI');

        $event = app(DraftPublisher::class)->publish($draft);
        $this->assertSame($this->region('toscana')->id, $event->region_id);

        $draft->update(['province' => 'MI']);
        $this->assertSame($this->region('lombardia')->id, app(DraftPublisher::class)->publish($draft->fresh())->region_id);
    }

    public function test_an_invalid_province_keeps_the_region_set_from_the_panel(): void
    {
        $draft = $this->activityDraft('FI');
        $event = app(DraftPublisher::class)->publish($draft);
        $event->update(['region_id' => $this->region('liguria')->id]);

        $draft->update(['province' => 'ZZ']);

        $this->assertSame($this->region('liguria')->id, app(DraftPublisher::class)->publish($draft->fresh())->region_id);
    }

    public function test_the_migration_fills_the_region_of_published_events_from_their_draft(): void
    {
        $tuscan = Event::factory()->create(['structure_draft_id' => $this->activityDraft('FI')->id]);
        $broken = Event::factory()->create(['structure_draft_id' => $this->activityDraft('ZZ')->id]);
        $demo = Event::factory()->create(['structure_draft_id' => null]);

        $migration = require base_path(self::MIGRATION);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('events', 'region_id'));

        $migration->up();
        $migration->up(); // rilanciabile

        $this->assertSame($this->region('toscana')->id, (int) DB::table('events')->where('id', $tuscan->id)->value('region_id'));
        $this->assertNull(DB::table('events')->where('id', $broken->id)->value('region_id'));
        $this->assertNull(DB::table('events')->where('id', $demo->id)->value('region_id'));
    }

    // ── /eventi ───────────────────────────────────────────────────────────────

    public function test_the_bar_lists_only_regions_with_listings_and_their_counts(): void
    {
        Event::factory()->count(2)->create(['region_id' => $this->region('lombardia')->id]);
        Event::factory()->create(['region_id' => $this->region('toscana')->id]);
        Event::factory()->create(['region_id' => $this->region('toscana')->id, 'suspended_at' => now()]);

        Livewire::test(Events::class)
            ->assertSeeInOrder([__('events.regions_all'), 'Lombardia (2)', 'Toscana (1)'])
            ->assertDontSee('Sicilia (')
            // Lo slug arriva al JavaScript del clic come stringa JSON, mai incollato a mano.
            ->assertSeeHtml("\$set('region', 'lombardia')");
    }

    public function test_a_region_filters_the_grid_and_is_shareable(): void
    {
        $milan = Event::factory()->create(['title' => 'Aperitivo milanese', 'region_id' => $this->region('lombardia')->id]);
        Event::factory()->create(['title' => 'Trekking toscano', 'region_id' => $this->region('toscana')->id]);

        Livewire::test(Events::class)
            ->call('$set', 'region', 'lombardia')
            ->assertSee('Aperitivo milanese')
            ->assertDontSee('Trekking toscano');

        $this->get(route('eventi', ['regione' => 'lombardia']))
            ->assertOk()
            ->assertSee($milan->title)
            ->assertDontSee('Trekking toscano');

        Livewire::withQueryParams(['regione' => 'inesistente'])
            ->test(Events::class)
            ->assertSee('Aperitivo milanese')
            ->assertSee('Trekking toscano');
    }

    public function test_a_selected_region_stays_in_the_bar_when_other_filters_empty_it(): void
    {
        Event::factory()->create(['region_id' => $this->region('lombardia')->id]);
        Event::factory()->activity()->create(['region_id' => $this->region('toscana')->id]);

        Livewire::test(Events::class)
            ->set('region', 'toscana')
            ->set('activeTypes', ['eventi'])
            ->assertSee('Toscana (0)');
    }

    public function test_finished_events_are_hidden_but_ongoing_ones_and_undated_activities_stay(): void
    {
        Event::factory()->create(['title' => 'Festa finita', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(2)]);
        Event::factory()->create(['title' => 'Corso in corso', 'starts_at' => now()->subDays(2), 'ends_at' => now()->addDays(2)]);
        Event::factory()->create(['title' => 'Senza fine passato', 'starts_at' => now()->subDay(), 'ends_at' => null]);
        Event::factory()->activity()->create(['title' => 'Dog sitter sempre']);
        Event::factory()->create(['title' => 'Concerto futuro']);

        Livewire::test(Events::class)
            ->assertDontSee('Festa finita')
            ->assertDontSee('Senza fine passato')
            ->assertSee('Corso in corso')
            ->assertSee('Dog sitter sempre')
            ->assertSee('Concerto futuro');

        $this->assertDatabaseHas('events', ['title->it' => 'Festa finita']);
    }

    public function test_only_finished_events_read_as_an_empty_catalogue(): void
    {
        Event::factory()->create(['starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)]);

        Livewire::test(Events::class)->assertSee(__('events.empty_catalogue_title'));
    }

    // ── Animal Holiday ────────────────────────────────────────────────────────

    public function test_the_region_page_lists_only_the_activities_of_that_region(): void
    {
        config(['app.seed_demo_data' => false]);
        Event::factory()->activity()->create(['title' => 'Maneggio lombardo', 'region_id' => $this->region('lombardia')->id]);
        Event::factory()->activity()->create(['title' => 'Maneggio valdostano', 'region_id' => $this->region('valle-daosta')->id]);

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['attivita'])
            ->assertSee('Maneggio lombardo')
            ->assertDontSee('Maneggio valdostano');
    }

    // ── Pannello ──────────────────────────────────────────────────────────────

    public function test_the_admin_filter_by_region_includes_events(): void
    {
        $event = Event::factory()->create(['region_id' => $this->region('toscana')->id]);
        Event::factory()->create(['region_id' => $this->region('lombardia')->id]);

        $page = app(CatalogAdmin::class)->paginate(['region' => (string) $this->region('toscana')->id]);

        $this->assertSame([$event->id], collect($page->items())->map->getKey()->all());
    }

    public function test_the_admin_can_correct_the_region_of_an_event(): void
    {
        $this->actingAsSuperadmin();
        $event = Event::factory()->create(['region_id' => null]);

        Livewire::test(CatalogShow::class, ['type' => 'event', 'id' => $event->id])
            ->set('regionId', (string) $this->region('liguria')->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($this->region('liguria')->id, $event->fresh()->region_id);
    }
}
