<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Segnale "pronta, in attesa di Stripe" (P4, 22/09/2026). Una bozza chiusa
     * da un partner online non ancora pagabile, senza questa colonna, non si
     * distingue da una abbandonata: il rollback riporta lo status a `draft` e
     * `current_step` non è affidabile (lo skip() dell'hotel lo lasciava a 10).
     * L'indice serve alla rete di sicurezza schedulata, che la cerca ogni
     * dieci minuti su tutta la tabella.
     */
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->timestamp('publish_requested_at')->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Prima l'indice: SQLite non toglie una colonna indicizzata.
            $table->dropIndex(['publish_requested_at']);
            $table->dropColumn('publish_requested_at');
        });
    }
};
