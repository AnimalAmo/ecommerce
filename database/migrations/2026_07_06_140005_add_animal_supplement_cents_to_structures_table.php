<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supplemento per animale in cents: a notte per le strutture, all'ora per i servizi.
     * 0 nei seed (i prezzi reali arriveranno dai partner B2B): totali XD invariati.
     */
    public function up(): void
    {
        Schema::table('structures', function (Blueprint $table) {
            $table->unsignedInteger('animal_supplement_cents')->default(0)->after('price_from_cents');
        });
    }

    public function down(): void
    {
        Schema::table('structures', function (Blueprint $table) {
            $table->dropColumn('animal_supplement_cents');
        });
    }
};
