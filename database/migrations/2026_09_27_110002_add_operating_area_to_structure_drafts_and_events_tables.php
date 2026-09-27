<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zona in cui opera l'attività (risposta della cliente, 27/09/2026): per
     * attività e servizi professionali prende il posto di «Data inizio / Data
     * fine», perché un toelettatore o un dog sitter non ha una data, ha un
     * raggio in cui lavora. Non sostituisce le date sulla colonna: le date
     * restano dove sono, facoltative, ed è lo step 3 a mostrare l'uno o le
     * altre.
     *
     * `text` e non `string`: il testo è libero e tradotto it/en con spatie,
     * che serializza le lingue in JSON dentro la colonna, come già fanno
     * `additional_other` e `activity_categories_other`.
     *
     * La gemella su `events` non è un lusso: senza di lei il valore resta
     * nella bozza del partner e la scheda pubblica non lo vede mai — la
     * stessa trappola già pagata con le categorie professionali.
     */
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->text('operating_area')->nullable()->after('activity_categories_other');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->text('operating_area')->nullable()->after('activity_categories_other');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn('operating_area');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('operating_area');
        });
    }
};
