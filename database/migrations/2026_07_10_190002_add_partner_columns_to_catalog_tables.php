<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara il catalogo B2C a ricevere i servizi pubblicati dai partner:
 * - ownership (user_id) + aggancio alla bozza sorgente (structure_draft_id,
 *   unique: ri-pubblicare aggiorna la stessa riga invece di duplicarla)
 * - cancellation_policy_days: la finestra di cancellazione scelta nel wizard
 *   (30/15/7/1), finora senza colonna catalogo
 * - structures.rating diventa nullable: i servizi partner nascono senza
 *   recensioni (i mock seedati continuano ad averlo).
 */
return new class extends Migration
{
    private const TABLES = ['structures', 'events', 'smartbox_packages'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
                $table->foreignId('structure_draft_id')->nullable()->unique()->after('user_id')
                    ->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('cancellation_policy_days')->nullable();
            });
        }

        Schema::table('structures', function (Blueprint $table): void {
            $table->decimal('rating', 2, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('structure_draft_id');
                $table->dropConstrainedForeignId('user_id');
                $table->dropColumn('cancellation_policy_days');
            });
        }

        Schema::table('structures', function (Blueprint $table): void {
            $table->decimal('rating', 2, 1)->nullable(false)->change();
        });
    }
};
