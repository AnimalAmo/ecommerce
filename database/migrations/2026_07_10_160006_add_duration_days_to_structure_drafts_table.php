<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Durata in giorni della smartbox (step 4 di 12 del flusso smartbox).
            $table->unsignedSmallInteger('duration_days')->nullable()->after('price_per_person');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn('duration_days');
        });
    }
};
