<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Capienza massima eventi/attività: null = illimitato. Nello step 3 la validazione
     * è read-only contro il massimo; il consumo posti arriva con gli ordini (step 4).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_participants')->nullable()->after('duration_days');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('max_participants');
        });
    }
};
