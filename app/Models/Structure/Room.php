<?php

namespace App\Models\Structure;

use App\Models\Concerns\HasAmenities;
use App\Services\Partner\ServiceOptionLabels;
use Database\Factories\Structure\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Stanza (tipologia con N unità identiche) di una struttura a camere.
 */
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasAmenities, HasFactory, HasTranslations;

    /** SOLO colonne stringa: `photos` è un array di path, non una mappa di locale. */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'structure_id',
        'draft_key',
        'type',
        'name',
        'description',
        'price_cents',
        'max_guests',
        'max_animals',
        'units',
        'photos',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'max_guests' => 'integer',
            'max_animals' => 'integer',
            'units' => 'integer',
            'photos' => 'array',
            'position' => 'integer',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    /** Nome della stanza, o l'etichetta della tipologia se il partner non l'ha scritto. */
    public function displayName(): string
    {
        $name = trim((string) $this->name);

        return $name !== ''
            ? $name
            : (string) ServiceOptionLabels::label('room_type', $this->type);
    }
}
