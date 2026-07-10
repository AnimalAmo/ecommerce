<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Step 8 di 12 del flusso smartbox ("Cosa è incluso?"): servizi struttura inclusi.
            $table->json('included_services')->nullable()->after('additional_other');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn('included_services');
        });
    }
};
