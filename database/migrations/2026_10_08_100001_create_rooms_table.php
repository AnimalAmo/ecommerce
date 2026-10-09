<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stanze di una struttura. Il publisher le sincronizza dalla bozza per
     * draft_key (uuid stabile della riga nel wizard): ripubblicare non cambia
     * l'id, quindi l'occupazione già registrata sulle righe ordine resta valida.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained()->cascadeOnDelete();
            $table->uuid('draft_key')->nullable();
            $table->string('type');
            // Translatable (json): il nome può mancare, vedi Room::displayName().
            $table->json('name')->nullable();
            $table->json('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedSmallInteger('max_guests')->default(2);
            $table->unsignedSmallInteger('max_animals')->default(1);
            // Quante camere identiche offre il partner: l'occupazione conta fino a units.
            $table->unsignedSmallInteger('units')->default(1);
            // Path sul disco public, come structures.gallery.
            $table->json('photos')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['structure_id', 'draft_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
