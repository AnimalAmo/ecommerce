<?php

namespace App\Models\Community;

use App\Models\User;
use Database\Factories\Community\CommunityPostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Post della community (Animal Network).
 *
 * Moderazione: `hidden_at` toglie il post dal sito (risposte comprese) senza
 * cancellarlo; `reports_count` conta le segnalazioni arrivate dopo l'ultima
 * valutazione del pannello (`moderated_at`), ed è quello che mette il post
 * nella coda "Segnalati". Il sito legge solo `visible()`: ogni query pubblica
 * deve passarci.
 */
class CommunityPost extends Model
{
    /** @use HasFactory<CommunityPostFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'author_name',
        'title',
        'tag',
        'body',
    ];

    /** Quello che il sito può mostrare. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('hidden_at');
    }

    /** In coda di moderazione: segnalato e ancora pubblicato. */
    public function scopeFlagged(Builder $query): Builder
    {
        return $query->whereNull('hidden_at')->where('reports_count', '>', 0);
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityPostReport::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommunityPostReply::class)->orderBy('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
            'moderated_at' => 'datetime',
            'reports_count' => 'integer',
        ];
    }
}
