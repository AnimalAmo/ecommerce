<?php

namespace App\Models\Structure;

use App\Enums\OrderPaymentMode;
use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasCatalogImages;
use App\Models\Concerns\HasCatalogModeration;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasMapEmbed;
use App\Models\Concerns\HasReviews;
use App\Models\Structure\Concerns\StructureHasRelationships;
use App\Services\Availability\RoomOccupancy;
use App\Services\Partner\PartnerPaymentModeService;
use Carbon\CarbonImmutable;
use Database\Factories\Structure\StructureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Structure extends Model
{
    /** @use HasFactory<StructureFactory> */
    use HasAmenities, HasCatalogImages, HasCatalogModeration, HasFactory, HasFaqs, HasMapEmbed, HasReviews, HasTranslations, StructureHasRelationships;

    /** SOLO colonne stringa — mai le json (general_info/features): spatie tratterebbe l'array come mappa di locale. */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'user_id',
        'structure_draft_id',
        'region_id',
        'type',
        'name',
        'slug',
        'location',
        'rating',
        'price_cents',
        'price_from_cents',
        'animal_supplement_cents',
        'img',
        'hero_img',
        // Foto della scheda nell'ordine della bozza, fotografate dal publisher («Vedere tutte le foto»).
        'gallery',
        'map_img',
        'description',
        'general_info',
        'features',
        'position',
        'cancellation_policy_days',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'gallery' => 'array',
            'rating' => 'float',
            'cancellation_policy_days' => 'integer',
            // Cast espliciti sui cents: il pricing (step 3) fa aritmetica, non solo display.
            'price_cents' => 'integer',
            'price_from_cents' => 'integer',
            'animal_supplement_cents' => 'integer',
            'general_info' => 'array',
            'features' => 'array',
        ];
    }

    /** Query place per la Maps Embed: i partner non hanno screenshot ma hanno la località. */
    public function mapQuery(): ?string
    {
        // Il solo nome non basta: Google centrerebbe un omonimo qualunque — meglio nascondere la sezione.
        if (blank($this->location)) {
            return null;
        }

        return collect([$this->name, $this->location])->filter()->implode(', ');
    }

    public function mapFallbackUrl(): ?string
    {
        return $this->mapImageUrl();
    }

    /**
     * Stanza proposta per date e gruppo (dettaglio struttura, «aggiungi al
     * carrello» dai preferiti), per posizione: la prima che contiene il gruppo
     * ed è libera nelle date; se sono tutte piene, la prima che lo contiene;
     * se nessuna lo contiene, la prima comunque (chi chiama riporta il gruppo
     * nella capienza). L'occupazione conta solo se il partner incassa online:
     * con pagamento in struttura è informativa e non blocca. Senza gruppo
     * (0/0) ogni stanza lo contiene. Null se la struttura non ha stanze.
     */
    public function defaultRoomFor(CarbonImmutable $checkIn, CarbonImmutable $checkOut, int $guests = 0, int $animals = 0): ?Room
    {
        $rooms = $this->rooms()->get();

        if ($rooms->isEmpty()) {
            return null;
        }

        $fitting = $rooms->filter(fn (Room $room): bool => $room->fits($guests, $animals));

        if (app(PartnerPaymentModeService::class)->forPurchasable($this) !== OrderPaymentMode::Online) {
            return $fitting->first() ?? $rooms->first();
        }

        $occupancy = app(RoomOccupancy::class);

        return $fitting->first(fn (Room $room): bool => $occupancy->isAvailable($room, $checkIn, $checkOut))
            ?? $fitting->first()
            ?? $rooms->first();
    }
}
