<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            // ProductType::Structure|Service — discrimina template (a notte vs a ora).
            $table->string('type');
            $table->string('name');
            // NON unico: il mock XD ripete gli stessi slug su card diverse.
            $table->string('slug')->index();
            $table->string('location');
            $table->decimal('rating', 2, 1);
            // Prezzo unitario del dettaglio (a notte per le strutture, all'ora per i servizi).
            $table->unsignedInteger('price_cents');
            // "A partire da" delle card listing (0 nel mock XD, incoerente col dettaglio: segnalato al cliente).
            $table->unsignedInteger('price_from_cents')->default(0);
            $table->string('img');
            $table->string('hero_img');
            $table->string('map_img');
            $table->text('description');
            // Righe "Informazioni generali": [{icon, title, lines: []}].
            $table->json('general_info');
            // Card "Cosa troverai": [{icon, title, lines: []}] — solo strutture, i servizi non hanno la sezione.
            $table->json('features')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structures');
    }
};
