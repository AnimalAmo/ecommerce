<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Province italiane (107, da database/locations-data/provinces.json — stesso
 * dataset di matsuri/storica): alimentano le select "Provincia" dei form
 * partner (iscrizione, profilo, luogo struttura/attività).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table): void {
            $table->id();
            $table->string('short_name', 5)->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
