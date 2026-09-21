<?php

namespace App\Models\Community;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Segnala" dal sito: una riga per utente e post (vincolo unico), così il
 * contatore del post non cresce a clic ripetuti.
 */
class CommunityPostReport extends Model
{
    protected $fillable = ['community_post_id', 'user_id'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
