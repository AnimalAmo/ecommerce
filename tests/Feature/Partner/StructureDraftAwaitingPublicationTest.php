<?php

namespace Tests\Feature\Partner;

use App\Models\Structure\StructureDraft;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Il segnale "pronta, in attesa di Stripe" (P4). Senza, una bozza chiusa da
 * un partner non ancora pagabile era indistinguibile da una abbandonata:
 * lo status tornava `draft` col rollback e lo step dell'hotel restava a 10.
 */
class StructureDraftAwaitingPublicationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_22_100003_add_publish_requested_at_to_structure_drafts_table.php';

    private function draft(array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 0,
        ], $attributes));
    }

    public function test_la_colonna_del_segnale_esiste_ed_e_indicizzata(): void
    {
        $this->assertTrue(Schema::hasColumn('structure_drafts', 'publish_requested_at'));
        $this->assertTrue(Schema::hasIndex('structure_drafts', ['publish_requested_at']));
    }

    public function test_il_rollback_toglie_indice_e_colonna(): void
    {
        $migration = require base_path(self::MIGRATION);

        $migration->down();

        $this->assertFalse(Schema::hasColumn('structure_drafts', 'publish_requested_at'));

        $migration->up();

        $this->assertTrue(Schema::hasIndex('structure_drafts', ['publish_requested_at']));
    }

    public function test_il_segnale_e_una_data(): void
    {
        $draft = $this->draft(['publish_requested_at' => '2026-09-22 10:00:00']);

        $this->assertInstanceOf(CarbonInterface::class, $draft->fresh()->publish_requested_at);
        $this->assertTrue($draft->fresh()->isAwaitingPublication());
        $this->assertFalse($this->draft()->isAwaitingPublication());
    }

    public function test_awaiting_publication_trova_solo_le_bozze_col_segnale(): void
    {
        $waiting = $this->draft(['publish_requested_at' => now()]);
        $this->draft();
        $this->draft(['status' => StructureDraft::STATUS_COMPLETED]);

        $this->assertSame([$waiting->id], StructureDraft::query()->awaitingPublication()->pluck('id')->all());
    }

    public function test_listable_for_elenca_completate_e_in_attesa_del_solo_partner(): void
    {
        $partner = User::factory()->create();
        $other = User::factory()->create();

        $completed = $this->draft(['user_id' => $partner->id, 'status' => StructureDraft::STATUS_COMPLETED]);
        $waiting = $this->draft(['user_id' => $partner->id, 'publish_requested_at' => now()]);
        $this->draft(['user_id' => $partner->id]);
        $this->draft(['user_id' => $other->id, 'status' => StructureDraft::STATUS_COMPLETED]);
        $this->draft(['user_id' => $other->id, 'publish_requested_at' => now()]);

        $this->assertEqualsCanonicalizing(
            [$completed->id, $waiting->id],
            StructureDraft::listableFor($partner->id)->pluck('id')->all(),
        );
    }

    public function test_lo_step_finale_dipende_dalla_famiglia(): void
    {
        $this->assertSame(11, $this->draft(['service_category' => 'struttura'])->finalStep());
        $this->assertSame(11, $this->draft(['service_category' => 'servizi'])->finalStep());
        $this->assertSame(11, $this->draft(['service_category' => 'attivita'])->finalStep());
        $this->assertSame(12, $this->draft(['service_category' => 'smartbox'])->finalStep());
        $this->assertSame(11, $this->draft()->finalStep());
    }
}
