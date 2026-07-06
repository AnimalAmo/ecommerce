<?php

namespace App\Models\Pet;

use App\Models\User;
use Database\Factories\Pet\PetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pet extends Model
{
    /** @use HasFactory<PetFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'species',
        'name',
        'size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
