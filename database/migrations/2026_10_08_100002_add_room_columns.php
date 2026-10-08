<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggancio delle stanze al resto del modello:
     * - order_items.room_id: stanza prenotata (null se cancellata: resta lo snapshot);
     * - structures/structure_drafts.merged_into_*: unione di strutture duplicate.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('purchasable_id')
                ->constrained('rooms')->nullOnDelete();
            // Query di occupazione: stanza + intervallo.
            $table->index(['room_id', 'booked_from', 'booked_until']);
        });

        Schema::table('structures', function (Blueprint $table) {
            $table->foreignId('merged_into_structure_id')->nullable()
                ->constrained('structures')->nullOnDelete();
        });

        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->foreignId('merged_into_draft_id')->nullable()
                ->constrained('structure_drafts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_into_draft_id');
        });

        Schema::table('structures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_into_structure_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'booked_from', 'booked_until']);
            $table->dropConstrainedForeignId('room_id');
        });
    }
};
