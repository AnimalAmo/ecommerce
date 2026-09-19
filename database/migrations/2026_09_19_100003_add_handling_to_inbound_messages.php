<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Segna come lavorata" e "Archivia" del pannello, su quello che arriva dai
     * due moduli del sito (contatti e candidature partner).
     */
    private const TABLES = ['contact_messages', 'partner_applications'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dateTime('handled_at')->nullable()->index();
                $table->dateTime('archived_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['handled_at']);
                $table->dropColumn(['handled_at', 'archived_at']);
            });
        }
    }
};
