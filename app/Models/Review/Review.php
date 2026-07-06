<?php

namespace App\Models\Review;

use Database\Factories\Review\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

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
        ];
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
