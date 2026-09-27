<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tipologia scelta nello step 2 dell'iscrizione non muore con
     * l'iscrizione (richiesta della cliente, 27/09/2026: «aggiungerei anche
     * "Evento" come quarta scelta già in fase di registrazione, così il
     * percorso è corretto fin dall'inizio»). Serve a preselezionare la card
     * giusta al primo ingresso in "Crea servizio": è un suggerimento, non un
     * vincolo — la schermata di scelta resta, e chi ha cambiato idea sceglie
     * altro. Per questo il funnel la legge e non la riscrive mai: qui dentro
     * resta ciò che il partner ha dichiarato iscrivendosi.
     *
     * Nullable e senza default: i partner già iscritti e quelli creati
     * dall'admin lo step 2 non l'hanno mai visto, e per loro non c'è nulla da
     * preselezionare. Stringa e non enum di database: le card cambiano a ogni
     * richiesta della cliente (oggi struttura|attivita|servizi|eventi) e un
     * enum andrebbe migrato ogni volta; chi legge il valore lo filtra da sé.
     * Nessun indice: si legge sempre per chiave primaria del profilo.
     */
    public function up(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->string('registration_service', 32)->nullable()->after('payment_url');
        });
    }

    public function down(): void
    {
        Schema::table('partner_profiles', function (Blueprint $table) {
            $table->dropColumn('registration_service');
        });
    }
};
