<?php

namespace App\Models\SmartboxPackage;

use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use Database\Factories\SmartboxPackage\SmartboxPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartboxPackage extends Model
{
    /** @use HasFactory<SmartboxPackageFactory> */
    use HasAmenities, HasFactory;

    protected $fillable = [
        'type',
        'title',
        'slug',
        'audience',
        'audience_people',
        'price_cents',
        'price_from_cents',
        'validity_months',
        'img',
        'hero_img',
        'description',
        'extended_description',
        'general_info',
        'features',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            // Cast espliciti sui cents: il pricing (step 3) fa aritmetica, non solo display.
            'price_cents' => 'integer',
            'price_from_cents' => 'integer',
            'general_info' => 'array',
            'features' => 'array',
        ];
    }
}
