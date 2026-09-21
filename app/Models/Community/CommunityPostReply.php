<?php

namespace App\Models\Community;

use App\Models\User;
use Database\Factories\Community\CommunityPostReplyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPostReply extends Model
{
    /** @use HasFactory<CommunityPostReplyFactory> */
    use HasFactory;

    protected $fillable = [
        'community_post_id',
        'user_id',
        'author_name',
        'body',
    ];

    /** Quello che il sito può mostrare (una risposta si nasconde anche da sola). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('hidden_at');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['hidden_at' => 'datetime'];
    }
}
