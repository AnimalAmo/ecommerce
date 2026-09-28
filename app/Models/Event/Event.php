<?php

namespace App\Models\Event;

use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasCatalogImages;
use App\Models\Concerns\HasCatalogModeration;
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
    use HasAmenities, HasCatalogImages, HasCatalogModeration, HasFactory, HasFaqs, HasTranslations;

    /**
     * SOLO colonne stringa — mai le json: spatie tratterebbe l'array come mappa di locale.
     *
     * Spatie legge '' e non null quando la lingua manca (e per una colonna
     * nulla): a valle il vuoto si controlla con blank()/filled(), mai `=== null`.
     */
    public array $translatable = ['title', 'description', 'detailed_description', 'activity_categories_other', 'operating_area', 'event_categories_other'];

    protected $fillable = [
        'user_id',
        'structure_draft_id',
        'venue_id',
        'type',
        'activity_categories',
        'activity_categories_other',
        // Gemelle delle colonne di bozza nate dalle risposte della cliente del
        // 27/09/2026: senza di loro il dato resta nella bozza e la scheda
        // pubblica non lo vede mai.
        'operating_area',
        'event_categories',
        'event_categories_other',
        'recurrence',
        'booking_requirement',
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
        // Gemella di `structure_drafts.detailed_description` (audit 28/09/2026,
        // difetto W1): il wizard la pretende per le attività, e senza colonna
        // qui restava nella bozza — la scheda ristampava la descrizione breve.
        'detailed_description',
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
            'activity_categories' => 'array',
            'event_categories' => 'array',
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
