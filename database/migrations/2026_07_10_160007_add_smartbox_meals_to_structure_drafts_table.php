<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Step 6 di 12 del flusso smartbox ("Cibo"): pasti offerti + restrizioni dietetiche.
            $table->json('meals')->nullable()->after('meal_times');
            $table->json('dietary_restrictions')->nullable()->after('meals');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn(['meals', 'dietary_restrictions']);
        });
    }
};
