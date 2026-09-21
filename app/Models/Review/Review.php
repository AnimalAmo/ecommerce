<?php

namespace App\Models\Review;

use App\Models\OrderItem\OrderItem;
use App\Models\Scopes\PublishedReviewScope;
use App\Models\User;
use Database\Factories\Review\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Recensione di una scheda a catalogo.
 *
 * `status`: `published` (sul sito), `pending` (scritta da un cliente, in
 * attesa di moderazione), `hidden` (tolta dal pannello). Il sito vede solo le
 * pubblicate (PublishedReviewScope); il pannello usa `withHidden()`.
 *
 * @method static Builder<static> withHidden()
 */
#[ScopedBy(PublishedReviewScope::class)]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'author_name',
        'author_initials',
        'avatar_color',
        'rating',
        'title',
        'body',
        'reviewed_at',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'reviewed_at' => 'date',
            'flagged_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    /** Tutte le recensioni, anche in attesa o nascoste: pannello e seeder. */
    public function scopeWithHidden(Builder $query): Builder
    {
        return $query->withoutGlobalScope(PublishedReviewScope::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Autore, quando la recensione arriva da un account (le seminate non ce l'hanno). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** La riga d'ordine recensita: la prova che chi scrive ha comprato. */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
