<?php

namespace App\Models\Scopes;

use App\Models\Review\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Le recensioni che il sito può mostrare: solo quelle pubblicate.
 *
 * Globale apposta, come per il catalogo: le recensioni si leggono dalla
 * relazione `reviews` delle schede (elenco, conteggio, "Carica altre") e da
 * `withCount('reviews')` nelle card della regione. Uno scope da ricordarsi di
 * chiamare sarebbe il modo più sicuro di dimenticarne uno, e una recensione
 * nascosta dal pannello che resta leggibile sulla scheda è esattamente il
 * difetto che la moderazione deve impedire.
 *
 * Chi deve vedere tutto lo toglie con `Review::withHidden()`: il pannello e
 * il seeder demo (che non deve duplicare una recensione nascosta).
 */
class PublishedReviewScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('status'), Review::STATUS_PUBLISHED);
    }
}
