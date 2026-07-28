<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Candidature inviate da un utente B2C già registrato: la riga resta
     * legata al suo account, così l'iscrizione B2B promuove quell'utente
     * invece di crearne uno nuovo (l'email sarebbe già presa).
     * Le candidature dei visitatori restano con user_id null.
     */
    public function up(): void
    {
        Schema::table('partner_applications', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('partner_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
