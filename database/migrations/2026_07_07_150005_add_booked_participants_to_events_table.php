<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consumo posti eventi/attività: incrementato da ReserveAvailabilityPipe sotto
     * lockForUpdate. La capienza residua è max_participants - booked_participants
     * (max_participants null = illimitato, il contatore resta comunque aggiornato).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('booked_participants')->default(0)->after('max_participants');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('booked_participants');
        });
    }
};
