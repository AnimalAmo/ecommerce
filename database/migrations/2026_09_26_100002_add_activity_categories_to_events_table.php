<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La gemella su `events` (richiesta della cliente, 26/09/2026). Senza
     * questa il valore resta nella bozza del partner e la scheda pubblica non
     * lo vede mai: è lo stesso buco che il piano segnalava per la ricorrenza
     * degli eventi.
     *
     * Le attività sono righe `events` con `type = attivita`: le categorie
     * restano NULL sugli eventi veri, che hanno una tipologia loro.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('activity_categories')->nullable()->after('type');
            // `text` e non `string`: il testo libero è tradotto it/en con
            // spatie, che serializza le lingue in JSON dentro la colonna, come
            // già fanno `additional_other` e `animal_services_other`.
            $table->text('activity_categories_other')->nullable()->after('activity_categories');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['activity_categories', 'activity_categories_other']);
        });
    }
};
