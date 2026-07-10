<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Costo del flusso Attività ed Eventi.
            $table->string('price_type')->nullable()->after('time_end'); // pagamento | gratuito
            $table->string('price_per_person')->nullable()->after('price_type');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn(['price_type', 'price_per_person']);
        });
    }
};
