<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            // ProductType::Event|Activity — discrimina scheda (evento singolo vs attività multi-giorno).
            $table->string('type');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('location');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            // Solo attività: null = weekend (3 giorni impliciti, riga durata assente in griglia come da XD).
            $table->unsignedTinyInteger('duration_days')->nullable();
            // Nullable + is_free (decisione ratificata #6): CTA Partecipa/Carrello derivata, non persistita.
            $table->unsignedInteger('price_cents')->nullable();
            $table->boolean('is_free')->default(false);
            $table->string('img');
            $table->string('hero_img')->nullable();
            $table->text('description')->nullable();
            // Sottotesti lorem delle righe "Informazioni generali" (orario e luogo).
            $table->text('time_note')->nullable();
            $table->text('venue_note')->nullable();
            // Ordine griglia /eventi (null = fuori griglia, es. card home).
            $table->unsignedSmallInteger('position')->nullable();
            // Ordine carosello home (null = fuori home).
            $table->unsignedSmallInteger('home_position')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
