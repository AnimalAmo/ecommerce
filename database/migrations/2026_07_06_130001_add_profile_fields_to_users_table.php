<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anagrafica cliente (step 2 — utenti): 'name' unico diventa nome/cognome
     * più i campi profilo del mock XD "Il mio account".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('id');
            $table->string('last_name')->after('first_name');
            $table->date('birth_date')->nullable()->after('last_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('postal_code', 10)->nullable()->after('city');
            $table->boolean('newsletter')->default(false)->after('postal_code');
            $table->boolean('marketing_consent')->default(false)->after('newsletter');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'birth_date',
                'phone',
                'address',
                'city',
                'postal_code',
                'newsletter',
                'marketing_consent',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });
    }
};
