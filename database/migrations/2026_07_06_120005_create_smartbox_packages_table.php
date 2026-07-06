<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smartbox_packages', function (Blueprint $table) {
            $table->id();
            // ProductType::Stay|Wellness|Adventure (tag della card).
            $table->string('type');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('audience');
            // "Coppia - 2 persone" nel dettaglio.
            $table->unsignedTinyInteger('audience_people')->default(2);
            $table->unsignedInteger('price_cents');
            // "A partire da" delle card listing (0 nel mock XD, incoerente col dettaglio: segnalato al cliente).
            $table->unsignedInteger('price_from_cents')->default(0);
            // "Valido per 1 anno" — item open-date (decisione ratificata #5).
            $table->unsignedTinyInteger('validity_months')->default(12);
            $table->string('img');
            $table->string('hero_img');
            $table->text('description');
            // Sezione "Il tuo weekend".
            $table->text('extended_description');
            $table->json('general_info');
            $table->json('features');
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smartbox_packages');
    }
};
