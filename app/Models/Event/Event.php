<?php

namespace App\Models\Event;

use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasFaqs;
use App\Models\Venue\Venue;
use Database\Factories\Event\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasAmenities, HasFactory, HasFaqs;

    protected $fillable = [
        'venue_id',
        'type',
        'title',
        'slug',
        'location',
        'starts_at',
        'ends_at',
        'duration_days',
        'max_participants',
        'price_cents',
        'is_free',
        'img',
        'hero_img',
        'description',
        'time_note',
        'venue_note',
        'position',
        'home_position',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            // Cast espliciti: capienza e prezzo entrano nell'aritmetica di availability/pricing (step 3).
            'max_participants' => 'integer',
            'price_cents' => 'integer',
            'is_free' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** CTA derivata (decisione ratificata #6): Partecipa quando gratis o senza prezzo, altrimenti Carrello. */
    public function hasJoinCta(): bool
    {
        return $this->is_free || $this->price_cents === null;
    }
}
