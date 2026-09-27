<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipologie di evento (risposta della cliente, 27/09/2026): passeggiate,
     * eventi educativi, corsi, sportivi, fattoria, fiere, solidali, speciali
     * pet-friendly, altro.
     *
     * Sono le gemelle di `activity_categories` sull'altro ramo dello stesso
     * step: le categorie professionali descrivono chi offre il servizio, queste
     * descrivono l'evento. Colonne separate e non un riuso, perché un partner
     * può passare da `attivita` a `eventi` e viceversa, e le due liste di slug
     * non si sovrappongono: la stessa colonna finirebbe per contenere slug che
     * il gruppo sbagliato non sa tradurre.
     *
     * JSON e non una stringa perché la scelta è MULTIPLA. La whitelist dei
     * singoli slug vive in ServiceOptionLabels, gruppo `event_category`, e la
     * regola va dentro `event_categories.*` — non sul valore intero, o
     * confronterebbe un array con delle stringhe e rifiuterebbe tutto.
     *
     * `event_categories_other` tiene il testo libero di "Altro", con lo stesso
     * nome che hanno già `activity_categories_other` e `additional_other`.
     */
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->json('event_categories')->nullable()->after('operating_area');
            // `text` e non `string`: il testo libero è tradotto it/en con
            // spatie, che serializza le lingue in JSON dentro la colonna.
            $table->text('event_categories_other')->nullable()->after('event_categories');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->json('event_categories')->nullable()->after('operating_area');
            $table->text('event_categories_other')->nullable()->after('event_categories');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn(['event_categories', 'event_categories_other']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['event_categories', 'event_categories_other']);
        });
    }
};
