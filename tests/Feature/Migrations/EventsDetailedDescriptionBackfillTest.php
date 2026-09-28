<?php

namespace Tests\Feature\Migrations;

use App\Models\Event\Event;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Difetto W1 (audit 28/09/2026): la colonna `events.detailed_description`
 * nasce vuota, e la sezione «Attività» della scheda da oggi stampa la
 * dettagliata e sparisce senza. Senza il travaso, ogni attività pubblicata
 * prima della correzione perderebbe quella sezione finché il partner non
 * ripubblica: il testo che ha scritto resta sulla bozza.
 *
 * Il travaso si prova come gira in produzione: righe già a catalogo PRIMA
 * della migrazione (down() toglie la colonna, up() la rimette e copia).
 */
class EventsDetailedDescriptionBackfillTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_28_100001_add_detailed_description_to_events_table.php';

    /** Riga a catalogo legata alla sua bozza, come la lascia EventPublisher. */
    private function publishedRow(string $type, mixed $detailed, bool $linked = true): Event
    {
        $draft = StructureDraft::create([
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => 'attivita',
            'type' => $type === 'event' ? 'eventi' : 'attivita',
        ]);

        // Scritta col query builder: così si può mettere anche il testo piano
        // di una bozza precedente alla conversione translatable.
        DB::table('structure_drafts')->where('id', $draft->id)->update([
            'detailed_description' => is_array($detailed) ? json_encode($detailed) : $detailed,
        ]);

        $factory = $type === 'event' ? Event::factory() : Event::factory()->activity();

        return $factory->create(['structure_draft_id' => $linked ? $draft->id : null]);
    }

    private function rawDetailed(Event $event): ?array
    {
        $raw = DB::table('events')->where('id', $event->id)->value('detailed_description');

        return $raw === null ? null : json_decode($raw, true);
    }

    public function test_il_travaso_copia_la_dettagliata_delle_attivita_gia_pubblicate(): void
    {
        $both = $this->publishedRow('activity', ['it' => 'Sei incontri.', 'en' => 'Six sessions.']);
        $italianOnly = $this->publishedRow('activity', ['it' => 'Solo italiano.', 'en' => '']);
        $legacy = $this->publishedRow('activity', 'Testo piano di una bozza legacy.');
        $blank = $this->publishedRow('activity', ['it' => '', 'en' => '']);
        $empty = $this->publishedRow('activity', null);
        $event = $this->publishedRow('event', ['it' => 'Residuo del ramo Attività.']);
        $demo = $this->publishedRow('activity', ['it' => 'Nessuno la collega.'], linked: false);

        $migration = require base_path(self::MIGRATION);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('events', 'detailed_description'));

        $migration->up();

        $this->assertSame(['it' => 'Sei incontri.', 'en' => 'Six sessions.'], $this->rawDetailed($both));
        // Solo le lingue compilate, come FamilyPublisher::translations().
        $this->assertSame(['it' => 'Solo italiano.'], $this->rawDetailed($italianOnly));
        // Il testo piano pre-translatable diventa italiano, non si perde.
        $this->assertSame(['it' => 'Testo piano di una bozza legacy.'], $this->rawDetailed($legacy));
        // Nessuna lingua compilata: la colonna resta NULL e la scheda nasconde la sezione.
        $this->assertNull($this->rawDetailed($blank));
        $this->assertNull($this->rawDetailed($empty));
        // Un evento vero non la prende, come nel publisher.
        $this->assertNull($this->rawDetailed($event));
        // Catalogo demo: nessuna bozza collegata, niente da copiare.
        $this->assertNull($this->rawDetailed($demo));

        // Spatie la legge come la scrive il publisher.
        $this->assertSame('Sei incontri.', $both->fresh()->getTranslation('detailed_description', 'it'));
    }

    /**
     * Idempotente: rilanciato dopo un'interruzione, o dopo che una
     * ripubblicazione ha già scritto la colonna, il travaso non sovrascrive il
     * testo più recente con quello della bozza.
     */
    public function test_il_travaso_non_sovrascrive_una_dettagliata_gia_scritta(): void
    {
        $republished = $this->publishedRow('activity', ['it' => 'Testo della bozza.']);
        $pending = $this->publishedRow('activity', ['it' => 'Ancora da copiare.']);

        DB::table('events')->where('id', $republished->id)->update([
            'detailed_description' => json_encode(['it' => 'Scritto dalla ripubblicazione.']),
        ]);

        $migration = require base_path(self::MIGRATION);
        (new ReflectionMethod($migration, 'copyFromDrafts'))->invoke($migration);

        $this->assertSame(['it' => 'Scritto dalla ripubblicazione.'], $this->rawDetailed($republished));
        $this->assertSame(['it' => 'Ancora da copiare.'], $this->rawDetailed($pending));
    }
}
