<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogo globale: le stesse voci ricorrono identiche su strutture/servizi/eventi/smartbox.
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // 'hotel' (Servizi Hotel / colonna sinistra) | 'animal' (Servizi Animali / colonna destra).
            $table->string('group');
            $table->timestamps();
        });

        // Pivot polimorfico con flag included (check verde / X magenta) e ordine riga.
        Schema::create('amenityables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->morphs('amenityable');
            $table->boolean('included');
            $table->unsignedTinyInteger('position');

            $table->unique(['amenity_id', 'amenityable_type', 'amenityable_id'], 'amenityables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenityables');
        Schema::dropIfExists('amenities');
    }
};
