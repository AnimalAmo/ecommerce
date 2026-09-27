<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorie professionali dell'attività (richiesta della cliente,
     * 26/09/2026): toelettatore, asilo per cani, dog sitter, educatore
     * cinofilo, fotografo pet, maneggio, fattoria didattica, altro.
     *
     * JSON e non una stringa perché la scelta è MULTIPLA — «una realtà può
     * rientrare in più categorie contemporaneamente, ad esempio maneggio +
     * fattoria didattica». La whitelist dei singoli slug vive in
     * ServiceOptionLabels, gruppo `activity_category`.
     *
     * `activity_categories_other` tiene il testo libero di "Altro", con lo
     * stesso nome che hanno già `additional_other` e `animal_services_other`.
     */
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->json('activity_categories')->nullable()->after('animal_services_other');
            // `text` e non `string`: il testo libero è tradotto it/en con
            // spatie, che serializza le lingue in JSON dentro la colonna, come
            // già fanno `additional_other` e `animal_services_other`.
            $table->text('activity_categories_other')->nullable()->after('activity_categories');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn(['activity_categories', 'activity_categories_other']);
        });
    }
};
