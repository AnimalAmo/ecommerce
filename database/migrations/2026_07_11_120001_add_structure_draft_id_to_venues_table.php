<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scoping del venue al draft di origine: senza chiave il publisher farebbe
     * firstOrCreate sul solo nome (meeting_point libero) — due partner con lo
     * stesso nome condividerebbero il venue (indirizzo altrui in pagina) e le
     * correzioni di indirizzo non si propagherebbero mai alla ri-pubblicazione.
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->foreignId('structure_draft_id')->nullable()->unique()->after('id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('structure_draft_id');
        });
    }
};
