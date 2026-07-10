<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Punto d'incontro del flusso "Attività ed Eventi" (step luogo).
            $table->string('meeting_point')->nullable()->after('license');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn('meeting_point');
        });
    }
};
