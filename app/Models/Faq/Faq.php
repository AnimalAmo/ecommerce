<?php

namespace App\Models\Faq;

use Database\Factories\Faq\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'position',
    ];

    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
