<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Il "Sommario" dell'editor di Animal Times: le righe sotto il titolo
     * nelle card, per lingua. Facoltativo: dove manca, le card continuano a
     * usare l'inizio del testo, come per gli articoli già pubblicati.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->json('excerpt')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('excerpt');
        });
    }
};
