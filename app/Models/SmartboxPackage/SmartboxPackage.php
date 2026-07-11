<?php

namespace App\Models\SmartboxPackage;

use App\Enums\ProductType;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasCatalogImages;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Database\Factories\SmartboxPackage\SmartboxPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class SmartboxPackage extends Model
{
    /** @use HasFactory<SmartboxPackageFactory> */
    use HasAmenities, HasCatalogImages, HasFactory, HasTranslations;

    /** SOLO colonne stringa — mai le json (general_info/features): spatie tratterebbe l'array come mappa di locale. */
    public array $translatable = ['title', 'description', 'extended_description'];

    protected $fillable = [
        'user_id',
        'structure_draft_id',
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
        'cancellation_policy_days',
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
            'cancellation_policy_days' => 'integer',
        ];
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
}
