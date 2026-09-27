<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ritiro dalla vetrina deciso dalla piattaforma, non dall'admin.
     *
     * Nasce dalla richiesta della cliente del 27/09/2026: «una Smartbox non
     * deve poter essere pubblicata/acquistata se il partner non ha collegato un
     * sistema di pagamento», «non la terrei in vetrina se non è acquistabile».
     * Una scheda ritirata esce dal catalogo come una sospesa, ma la causa è
     * un'altra e va tenuta separata: `suspended_at` è il "togli struttura" del
     * pannello e il partner lo legge come «Sospesa», cioè come un'accusa; il
     * ritiro si annulla da sé quando il partner torna all'incasso online e la
     * ripubblicazione riscrive la riga.
     *
     * Su tutte e tre le tabelle come per `suspended_at` (vedi
     * 2026_09_19_100001): CatalogVisibleScope è uno scope globale condiviso dai
     * tre model e qualifica le colonne con `qualifyColumn()` — su una tabella
     * sola le altre due esploderebbero al primo `Structure::query()`.
     *
     * Senza indice: la colonna è quasi sempre NULL e non seleziona nulla da
     * sola. Se servirà, andrà nell'indice composto ['approval_status',
     * 'suspended_at'], non accanto.
     */
    private const TABLES = ['structures', 'events', 'smartbox_packages'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dateTime('withheld_at')->nullable()->after('suspended_at');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('withheld_at');
            });
        }
    }
};
