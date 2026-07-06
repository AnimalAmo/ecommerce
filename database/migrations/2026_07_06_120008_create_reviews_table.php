<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            // Autore denormalizzato: le recensioni utente reali arrivano con lo step 2 (FK user).
            $table->string('author_name');
            $table->string('author_initials', 4);
            $table->string('avatar_color', 9);
            // Step 0.5 (decisione ratificata: rating 0.5-step nei detail).
            $table->decimal('rating', 2, 1);
            $table->string('title');
            $table->text('body');
            $table->date('reviewed_at');
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
