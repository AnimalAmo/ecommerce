<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registrazione rapida (cliente, 06/10/2026): sul sito si chiedono solo
     * nome, email e password, si entra subito nel profilo e il resto si
     * completa quando si vuole. Il cognome diventa facoltativo come gli altri
     * campi del profilo, che lo erano già a database.
     *
     * `terms_accepted_at`: quando ha accettato termini e informativa privacy,
     * la casella obbligatoria del modulo. Prima non c'era nessuna casella del
     * genere (docs/analisi-entita-dinamiche.md), quindi gli account già
     * esistenti restano a NULL.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->change();
        });

        if (! Schema::hasColumn('users', 'terms_accepted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('terms_accepted_at')->nullable()->after('marketing_consent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'terms_accepted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('terms_accepted_at');
            });
        }

        // Il cognome resta nullable: riportarlo NOT NULL fallirebbe sugli
        // account nati con la registrazione rapida.
    }
};
