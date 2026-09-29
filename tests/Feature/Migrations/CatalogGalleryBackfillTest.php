<?php

namespace Tests\Feature\Migrations;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * La colonna `gallery` nasce vuota sulle tre tabelle del catalogo, e senza
 * galleria la scheda mostra la sola copertina: il pulsante «Vedere tutte le
 * foto» sparirebbe da ogni scheda pubblicata prima della correzione finché il
 * partner non ripubblica. Le foto stanno sulla bozza, e il travaso le copia
 * come farebbe una ripubblicazione.
 *
 * Si prova come gira in produzione: righe già a catalogo PRIMA della
 * migrazione (down() toglie la colonna, up() la rimette e copia).
 */
class CatalogGalleryBackfillTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_29_120001_add_gallery_to_catalog_tables.php';

    private const TABLES = ['structures', 'events', 'smartbox_packages'];

    private function draftWith(?array $photos): StructureDraft
    {
        return StructureDraft::create([
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'photos' => $photos,
        ]);
    }

    private function rawGallery(Model $row): ?array
    {
        $raw = DB::table($row->getTable())->where('id', $row->id)->value('gallery');

        return $raw === null ? null : json_decode($raw, true);
    }

    public function test_il_travaso_copia_le_foto_della_bozza_sulle_tre_famiglie(): void
    {
        $hotelPhotos = ['structure-photos/h1.jpg', 'structure-photos/h2.jpg', 'structure-photos/h3.jpg', 'structure-photos/h4.jpg'];
        $activityPhotos = ['structure-photos/a1.jpg', 'structure-photos/a2.jpg', 'structure-photos/a3.jpg', 'structure-photos/a4.jpg'];
        $boxPhotos = ['smartbox-photos/b1.jpg', 'smartbox-photos/b2.jpg', 'smartbox-photos/b3.jpg', 'smartbox-photos/b4.jpg'];

        $structure = Structure::factory()->create(['structure_draft_id' => $this->draftWith($hotelPhotos)->id]);
        $activity = Event::factory()->activity()->create(['structure_draft_id' => $this->draftWith($activityPhotos)->id]);
        $box = SmartboxPackage::factory()->create(['structure_draft_id' => $this->draftWith($boxPhotos)->id]);
        $withoutPhotos = Event::factory()->create(['structure_draft_id' => $this->draftWith(null)->id]);
        $demo = Structure::factory()->create(['structure_draft_id' => null]);

        $migration = require base_path(self::MIGRATION);
        $migration->down();
        foreach (self::TABLES as $table) {
            $this->assertFalse(Schema::hasColumn($table, 'gallery'), "down() deve togliere gallery da {$table}.");
        }

        $migration->up();

        $this->assertSame($hotelPhotos, $this->rawGallery($structure));
        $this->assertSame($activityPhotos, $this->rawGallery($activity));
        $this->assertSame($boxPhotos, $this->rawGallery($box));
        // Una bozza senza foto non ha niente da copiare: resta la sola copertina.
        $this->assertNull($this->rawGallery($withoutPhotos));
        // Catalogo demo: nessuna bozza collegata.
        $this->assertNull($this->rawGallery($demo));

        // Eloquent la legge come la scrive il publisher.
        $this->assertSame($activityPhotos, $activity->fresh()->gallery);
    }

    /** Rilanciata dopo un'interruzione non riscrive quello che una ripubblicazione ha già fotografato. */
    public function test_il_travaso_non_sovrascrive_una_galleria_gia_scritta(): void
    {
        $draft = $this->draftWith(['structure-photos/bozza-1.jpg', 'structure-photos/bozza-2.jpg']);
        $activity = Event::factory()->activity()->create(['structure_draft_id' => $draft->id]);

        $migration = require base_path(self::MIGRATION);
        $migration->down();
        $migration->up();

        $published = ['structure-photos/pubblicata-1.jpg', 'structure-photos/pubblicata-2.jpg'];
        DB::table('events')->where('id', $activity->id)->update(['gallery' => json_encode($published)]);

        $migration->up();

        $this->assertSame($published, $this->rawGallery($activity));
    }
}
