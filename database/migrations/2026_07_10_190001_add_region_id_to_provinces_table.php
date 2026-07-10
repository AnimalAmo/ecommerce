<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collega ogni provincia alla sua regione (ripartizione ISTAT, 107 → 20):
 * il publisher dei servizi partner deriva structures.region_id dalla
 * provincia scelta nel wizard. La mappa vive in ProvinceSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table): void {
            $table->foreignId('region_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
