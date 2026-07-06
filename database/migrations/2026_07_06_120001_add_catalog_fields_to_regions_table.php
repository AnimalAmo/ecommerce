<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->string('img')->nullable()->after('slug');
            // Ordine griglia Animal Holiday (riga per riga come da XD, non alfabetico).
            $table->unsignedSmallInteger('position')->nullable()->after('img');
            // Le 3 card della home (Liguria, Veneto, Trentino nel mock).
            $table->unsignedSmallInteger('home_position')->nullable()->after('position');
            // Contatore mostrato sul badge card; dal mock XD finché il catalogo per regione non è reale.
            $table->unsignedSmallInteger('structures_count')->default(0)->after('home_position');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['img', 'position', 'home_position', 'structures_count']);
        });
    }
};
