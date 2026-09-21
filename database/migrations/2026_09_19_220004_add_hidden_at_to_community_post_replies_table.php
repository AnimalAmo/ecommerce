<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moderazione della community: le fondamenta del pannello hanno dato ai
     * post `hidden_at`, segnalazioni e data di valutazione. Anche una singola
     * risposta si può nascondere dal pannello senza toccare il post.
     */
    public function up(): void
    {
        Schema::table('community_post_replies', function (Blueprint $table) {
            $table->dateTime('hidden_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('community_post_replies', function (Blueprint $table) {
            $table->dropIndex(['hidden_at']);
            $table->dropColumn('hidden_at');
        });
    }
};
