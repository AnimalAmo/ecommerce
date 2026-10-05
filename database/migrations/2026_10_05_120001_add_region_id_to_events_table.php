<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Regione di attività ed eventi (richiesta della cliente, 01/10/2026:
     * «suddividere attività ed eventi per regione, come le strutture»).
     *
     * Le strutture la hanno dal 10/07 e la ricavano dalla provincia della
     * bozza (provinces.region_id, mappa ISTAT). Attività ed eventi chiedono la
     * stessa provincia, obbligatoria, nello step «Luogo», ma finora finiva solo
     * nell'etichetta «Città (PROV), Italia». Nullable: le schede del catalogo
     * demo non hanno una bozza, e una provincia non valida non deve bloccare
     * una pubblicazione.
     */
    public function up(): void
    {
        // Su MySQL il DDL non è transazionale: la guardia rende rilanciabile
        // anche la metà del travaso (vedi 2026_09_29_120001).
        if (! Schema::hasColumn('events', 'region_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->foreignId('region_id')->nullable()->after('location')->constrained('regions')->nullOnDelete();
            });
        }

        $this->copyFromDrafts();
    }

    public function down(): void
    {
        if (Schema::hasColumn('events', 'region_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropConstrainedForeignId('region_id');
            });
        }
    }

    /**
     * Le schede già pubblicate prendono la regione dalla provincia della loro
     * bozza, la stessa che una ripubblicazione userebbe. Idempotente: solo le
     * righe ancora senza regione. Quelle che restano nulle (catalogo demo,
     * provincia non valida) compaiono solo sotto «Tutte»; si contano con
     *
     *   select count(*) from events where region_id is null and structure_draft_id is not null;
     */
    private function copyFromDrafts(): void
    {
        DB::table('events')
            ->join('structure_drafts', 'structure_drafts.id', '=', 'events.structure_draft_id')
            ->join('provinces', 'provinces.short_name', '=', 'structure_drafts.province')
            ->whereNull('events.region_id')
            ->whereNotNull('provinces.region_id')
            ->select('events.id as row_id', 'provinces.region_id as source')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('events')->where('id', $row->row_id)->update(['region_id' => $row->source]);
                }
            }, 'events.id', 'row_id');
    }
};
