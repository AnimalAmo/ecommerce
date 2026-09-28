<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avviso in sospeso del carrello (difetto C9, audit 28/09/2026): le righe
     * tolte perché il prodotto è uscito dal catalogo — ritirato, sospeso,
     * cancellato — e che il cliente non ha ancora visto. Serve una colonna e
     * non la sessione perché le righe spariscono nella richiesta di qualcun
     * altro (l'admin che sospende), e il cliente torna quando torna.
     *
     * JSON: lista di {title: {locale: string}, reason, message}, vedi
     * App\Services\Cart\CartNotice. Null = niente da dire.
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->json('notice')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('notice');
        });
    }
};
