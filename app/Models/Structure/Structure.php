<?php

namespace App\Models\Structure;

use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasCatalogImages;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasReviews;
use App\Models\Structure\Concerns\StructureHasRelationships;
use Database\Factories\Structure\StructureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Structure extends Model
{
    /** @use HasFactory<StructureFactory> */
    use HasAmenities, HasCatalogImages, HasFactory, HasFaqs, HasReviews, HasTranslations, StructureHasRelationships;

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
}
