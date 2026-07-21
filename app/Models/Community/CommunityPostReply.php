<?php

namespace App\Models\Community;

use App\Models\User;
use Database\Factories\Community\CommunityPostReplyFactory;
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

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
