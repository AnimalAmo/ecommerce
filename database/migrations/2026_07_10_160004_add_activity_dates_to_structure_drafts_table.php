<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            // Informazioni generali del flusso Attività ed Eventi (date + orari evento).
            $table->date('date_start')->nullable()->after('detailed_description');
            $table->date('date_end')->nullable()->after('date_start');
            $table->string('time_start')->nullable()->after('date_end');
            $table->string('time_end')->nullable()->after('time_start');
        });
    }

    public function down(): void
    {
        Schema::table('structure_drafts', function (Blueprint $table) {
            $table->dropColumn(['date_start', 'date_end', 'time_start', 'time_end']);
        });
    }
};
