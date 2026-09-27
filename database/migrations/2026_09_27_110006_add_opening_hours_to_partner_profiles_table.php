<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orari di apertura o disponibilità (risposta della cliente, 27/09/2026).
     *
     * Sta sul profilo partner e non sulla bozza, ed è un campo solo: la cliente
     * li nomina sia fra i campi dell'attività sia fra i recapiti pubblici, e due
     * campi che possono contraddirsi sono peggio di uno. Le schede li leggono
     * dal profilo del partner, quindi non serve la gemella sulle tabelle di
     * catalogo: il dato non passa dalla bozza.
     *
     * `text` e non `string`: è testo libero e tradotto it/en con spatie, che
     * serializza le lingue in JSON dentro la colonna. Va dopo `zip`, cioè in
     * coda al blocco dei dati dell'attività, non in mezzo alle coordinate di
     * pagamento.
     */
    public function up(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->text('opening_hours')->nullable()->after('zip');
        });
    }

    public function down(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->dropColumn('opening_hours');
        });
    }
};
