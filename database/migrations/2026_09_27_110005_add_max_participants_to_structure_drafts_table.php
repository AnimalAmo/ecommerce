<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Posti disponibili nella bozza (risposta della cliente, 27/09/2026): null
     * = illimitato, come sulla gemella di `events`.
     *
     * Su `events` la colonna esiste dal 06/07 (2026_07_06_140004) e la capienza
     * la usa davvero AvailabilityService, ma EventPublisher scriveva
     * `max_participants => null` fisso perché il wizard non aveva un campo da
     * cui leggerla: mancava il capo dalla parte della bozza. Stesso tipo della
     * gemella — `unsignedSmallInteger` — o il publisher copierebbe un valore che
     * la colonna di catalogo non riesce a contenere.
     */
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_participants')->nullable()->after('duration_days');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn('max_participants');
        });
    }
};
