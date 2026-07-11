<?php

namespace App\Models\Event;

use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasCatalogImages;
use App\Models\Concerns\HasFaqs;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Models\Venue\Venue;
use Database\Factories\Event\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasAmenities, HasCatalogImages, HasFactory, HasFaqs, HasTranslations;

    /** SOLO colonne stringa — mai le json: spatie tratterebbe l'array come mappa di locale. */
    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'user_id',
        'structure_draft_id',
        'venue_id',
        'type',
        'title',
        'slug',
        'location',
        'starts_at',
        'ends_at',
        'duration_days',
        'max_participants',
        'booked_participants',
        'price_cents',
        'is_free',
        'img',
        'hero_img',
        'description',
        'time_note',
        'venue_note',
        'position',
        'home_position',
        'cancellation_policy_days',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancellation_policy_days' => 'integer',
            // Cast espliciti: capienza e prezzo entrano nell'aritmetica di availability/pricing (step 3).
            'max_participants' => 'integer',
            // Consumo posti (step 4): incrementato da ReserveAvailabilityPipe sotto lock.
            'booked_participants' => 'integer',
            'price_cents' => 'integer',
            'is_free' => 'boolean',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** Partner proprietario (null per le righe seedate della piattaforma). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Draft di origine: la ri-pubblicazione aggiorna la stessa riga (unique su structure_draft_id). */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(StructureDraft::class, 'structure_draft_id');
    }

    /** CTA derivata (decisione ratificata #6): Partecipa quando gratis o senza prezzo, altrimenti Carrello. */
    public function hasJoinCta(): bool
    {
        return $this->is_free || $this->price_cents === null;
    }
}
