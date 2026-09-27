<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ricorrenza e prenotazione (risposta della cliente, 27/09/2026).
     *
     * `recurrence` è una sola etichetta sulla scheda ('singolo' | 'ricorrente'):
     * la cliente ha escluso di generare le date ripetute in questa fase («non
     * serve in questa fase creare automaticamente tutte le ricorrenze»), quindi
     * NON è una regola di ripetizione e non va letta come tale. Il giorno in cui
     * lo diventerà servirà una tabella di occorrenze, non un cast su questa
     * colonna.
     *
     * `booking_requirement` è UNA colonna per attività ed eventi: la cliente
     * chiede la «possibilità di prenotazione» per i professionisti e
     * «obbligatoria o facoltativa» per gli eventi, che è la stessa informazione
     * con tre stati ('obbligatoria' | 'facoltativa' | 'non_prevista'). Due
     * colonne quasi omonime si contraddirebbero appena il partner cambia ramo.
     *
     * Stringhe e non enum: gli slug sono whitelistati in ServiceOptionLabels
     * (gruppi `event_recurrence` e `booking_requirement`), che è anche ciò che
     * disegna i controlli — un enum PHP aggiungerebbe una seconda lista da
     * tenere allineata a mano.
     */
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->string('recurrence')->nullable()->after('event_categories_other');
            $table->string('booking_requirement')->nullable()->after('recurrence');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('recurrence')->nullable()->after('event_categories_other');
            $table->string('booking_requirement')->nullable()->after('recurrence');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn(['recurrence', 'booking_requirement']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['recurrence', 'booking_requirement']);
        });
    }
};
