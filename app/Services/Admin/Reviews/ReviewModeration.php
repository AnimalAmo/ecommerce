<?php

namespace App\Services\Admin\Reviews;

use App\Models\Concerns\HasReviews;
use App\Models\Review\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Moderazione delle recensioni dal pannello: pubblica (anche "ripristina"
 * una nascosta), nascondi, elimina.
 *
 * Il voto medio che il sito mostra è la colonna `rating` della scheda (card
 * della regione, testata e riepilogo recensioni della scheda, card dei
 * preferiti): ogni cambio di stato la ricalcola sulle sole pubblicate, così
 * una recensione nascosta smette di pesare sulla media nello stesso momento
 * in cui sparisce dall'elenco. Senza pubblicate la media torna a null e il
 * sito mostra "Nuovo".
 */
class ReviewModeration
{
    public const TABS = [Review::STATUS_PENDING, Review::STATUS_PUBLISHED, Review::STATUS_HIDDEN];

    /** @return Builder<Review> */
    public function query(string $status, string $search = ''): Builder
    {
        $query = Review::withHidden()
            ->where('status', in_array($status, self::TABS, true) ? $status : Review::STATUS_PENDING)
            // La scheda anche se sospesa o in attesa: al pannello serve il nome.
            ->with(['reviewable' => fn (MorphTo $morph) => $morph->withoutGlobalScopes(), 'user'])
            ->latest('created_at')
            ->latest('id');

        foreach (preg_split('/\s+/', mb_strtolower(trim($search)), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $word).'%';

            $query->where(function (Builder $q) use ($term): void {
                $q->whereRaw('lower(author_name) like ?', [$term])
                    ->orWhereRaw('lower(title) like ?', [$term])
                    ->orWhereRaw('lower(body) like ?', [$term]);
            });
        }

        return $query;
    }

    /** @return array<string, int> Recensioni per stato, per i contatori delle schede. */
    public function counts(): array
    {
        $counts = Review::withHidden()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(self::TABS)->mapWithKeys(fn (string $status): array => [$status => (int) ($counts[$status] ?? 0)])->all();
    }

    public function find(int $id): ?Review
    {
        return Review::withHidden()->find($id);
    }

    /** Pubblica una recensione in attesa, o rimette online una nascosta. */
    public function publish(Review $review): void
    {
        $this->setStatus($review, Review::STATUS_PUBLISHED);
    }

    public function hide(Review $review): void
    {
        $this->setStatus($review, Review::STATUS_HIDDEN);
    }

    public function delete(Review $review): void
    {
        DB::transaction(function () use ($review): void {
            $review->delete();
            $this->refreshRating($review->reviewable_type, (int) $review->reviewable_id);
        });
    }

    /**
     * Media delle recensioni pubblicate, scritta sulla scheda (null se non ce
     * ne sono). Solo per le famiglie che mostrano recensioni sul sito (trait
     * HasReviews): le altre non hanno la colonna `rating`.
     */
    public function refreshRating(string $type, int $id): void
    {
        $class = Relation::getMorphedModel($type);

        if ($class === null || ! in_array(HasReviews::class, class_uses_recursive($class), true)) {
            return;
        }

        // Query builder e non il model: la scheda può essere sospesa (fuori dallo scope del catalogo).
        $average = DB::table('reviews')
            ->where('reviewable_type', $type)
            ->where('reviewable_id', $id)
            ->where('status', Review::STATUS_PUBLISHED)
            ->avg('rating');

        $model = new $class;

        DB::table($model->getTable())
            ->where($model->getKeyName(), $id)
            ->update(['rating' => $average === null ? null : round((float) $average, 1)]);
    }

    private function setStatus(Review $review, string $status): void
    {
        DB::transaction(function () use ($review, $status): void {
            $review->forceFill(['status' => $status, 'moderated_at' => now()])->save();
            $this->refreshRating($review->reviewable_type, (int) $review->reviewable_id);
        });
    }
}
