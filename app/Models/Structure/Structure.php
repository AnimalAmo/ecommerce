<?php

namespace App\Models\Structure;

use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasFaqs;
use App\Models\Concerns\HasReviews;
use App\Models\Region\Region;
use Database\Factories\Structure\StructureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Structure extends Model
{
    /** @use HasFactory<StructureFactory> */
    use HasAmenities, HasFactory, HasFaqs, HasReviews;

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'rating' => 'float',
            // Cast espliciti sui cents: il pricing (step 3) fa aritmetica, non solo display.
            'price_cents' => 'integer',
            'price_from_cents' => 'integer',
            'animal_supplement_cents' => 'integer',
            'general_info' => 'array',
            'features' => 'array',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** Giorni di chiusura (calendario disponibilità, step 3). */
    public function closures(): HasMany
    {
        return $this->hasMany(StructureClosure::class);
    }
}
