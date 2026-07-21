<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Post e risposte della community (Animal Network).
     *
     * L'autore è denormalizzato in `author_name`: i post seed non hanno un account
     * dietro e la firma deve restare leggibile anche se l'utente viene cancellato.
     * `user_id` resta la fonte di verità per il tab "I miei post".
     */
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 64);
            $table->string('title', 128);
            $table->string('tag', 32)->index();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('community_post_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 64);
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_replies');
        Schema::dropIfExists('community_posts');
    }
};
