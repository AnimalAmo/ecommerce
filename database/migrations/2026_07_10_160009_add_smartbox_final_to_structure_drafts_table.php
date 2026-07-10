<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Step 10 (strutture incluse nella smartbox) e step 12 (costo totale).
            $table->json('smartbox_structures')->nullable()->after('smartbox_types');
            $table->string('price')->nullable()->after('price_per_person');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn(['smartbox_structures', 'price']);
        });
    }
};
