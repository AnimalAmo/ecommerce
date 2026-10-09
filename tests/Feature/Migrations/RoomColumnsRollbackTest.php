<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * La down() delle colonne stanza deve togliere la FK prima dell'indice
 * composito su cui MySQL la appoggia (errore 1553 altrimenti). Qui gira su
 * sqlite, che non fa quel controllo: il test copre che la down() completa e
 * che la up() si possa rieseguire, l'ordine resta garantito dal codice.
 */
class RoomColumnsRollbackTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_08_100002_add_room_columns.php';

    public function test_room_columns_roll_back_and_reapply(): void
    {
        $migration = require base_path(self::MIGRATION);

        $migration->down();

        $this->assertFalse(Schema::hasColumn('order_items', 'room_id'));
        $this->assertFalse(Schema::hasColumn('structures', 'merged_into_structure_id'));
        $this->assertFalse(Schema::hasColumn('structure_drafts', 'merged_into_draft_id'));

        $migration->up();

        $this->assertTrue(Schema::hasColumn('order_items', 'room_id'));
    }
}
