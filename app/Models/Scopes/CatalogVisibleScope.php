<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Ciò che il sito può mostrare e vendere: schede non sospese e approvate.
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
            ->where($model->qualifyColumn('approval_status'), 'approved');
    }
}
