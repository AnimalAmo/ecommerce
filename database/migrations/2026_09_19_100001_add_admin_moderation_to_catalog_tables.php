<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stati amministrativi delle schede a catalogo, uguali sulle tre famiglie.
     *
     * `suspended_at` è il "togli struttura" del pannello: la scheda sparisce dal
     * sito ma la riga resta, perché carrelli e preferiti la referenziano con una
     * relazione polimorfica senza vincolo di integrità — cancellarla davvero
     * lascerebbe riferimenti appesi nel vuoto.
     *
     * `approval_status` è la moderazione preventiva: `approved` di default, così
     * le righe esistenti e quelle pubblicate a moderazione spenta restano visibili
     * senza backfill. `approval_note` è il testo di "Chiedi modifiche" che arriva
     * al partner.
     */
    private const TABLES = ['structures', 'events', 'smartbox_packages'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dateTime('suspended_at')->nullable();
                $table->string('approval_status', 32)->default('approved');
                $table->text('approval_note')->nullable();
                $table->dateTime('approval_requested_at')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->index(['approval_status', 'suspended_at']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['approval_status', 'suspended_at']);
                $table->dropColumn(['suspended_at', 'approval_status', 'approval_note', 'approval_requested_at', 'approved_at']);
            });
        }
    }
};
