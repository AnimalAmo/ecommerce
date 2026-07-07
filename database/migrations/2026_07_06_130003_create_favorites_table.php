<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferiti persistenti: morph verso il catalogo (structure/event/smartbox_package,
     * alias della morph map in AppServiceProvider). Unique = un cuore per prodotto.
     */
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
