<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Giorni di chiusura per struttura/servizio: una riga per data non prenotabile.
     * Consumata da AvailabilityService (check range/giorno) e dai calendari widget.
     */
    public function up(): void
    {
        Schema::create('structure_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained()->cascadeOnDelete();
            $table->date('date');

            $table->unique(['structure_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_closures');
    }
};
