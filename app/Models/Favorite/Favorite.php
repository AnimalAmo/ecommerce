<?php

namespace App\Models\Favorite;

use App\Models\User;
use Database\Factories\Favorite\FavoriteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Favorite extends Model
{
    /** @use HasFactory<FavoriteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favoritable_type',
        'favoritable_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Prodotto preferito: structure/event/smartbox_package (morph map). */
    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }
}
