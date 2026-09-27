<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Ciò che il sito può mostrare e vendere: schede non sospese, non ritirate e
 * approvate.
 *
 * `withheld_at` è il ritiro della piattaforma (27/09/2026: una smartbox il cui
 * partner non incassa online non è acquistabile, quindi non sta in vetrina).
 * Sta qui e non in una condizione sparsa per il codice per la stessa ragione di
 * `suspended_at`: una scheda non acquistabile che resta comprabile da un link
 * diretto è esattamente il difetto che il ritiro deve impedire.
 *
 * Globale apposta. Il catalogo è interrogato da una ventina di punti (liste,
 * dettagli, carrello, checkout, sitemap, conteggi per regione): uno scope da
 * ricordarsi di chiamare sarebbe il modo più sicuro di dimenticarne uno, e una
 * scheda sospesa che resta comprabile da un link diretto è esattamente il
 * difetto che la sospensione deve impedire.
 *
 * Chi deve vedere tutto lo toglie esplicitamente: il pannello, l'area partner
 * (le schede sono le sue), i publisher, gli ordini già fatti (la relazione
 * purchasable di OrderItem), i comandi di manutenzione.
 */
class CatalogVisibleScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder
            ->whereNull($model->qualifyColumn('suspended_at'))
            ->whereNull($model->qualifyColumn('withheld_at'))
            ->where($model->qualifyColumn('approval_status'), 'approved');
    }
}
