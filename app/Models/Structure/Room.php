<?php

namespace App\Models\Structure;

use App\Models\Concerns\HasAmenities;
use App\Services\Partner\ServiceOptionLabels;
use Database\Factories\Structure\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
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

    /** Etichetta localizzata della tipologia («Doppia», «Suite»…). */
    public function typeLabel(): string
    {
        return (string) ServiceOptionLabels::label('room_type', $this->type);
    }

    /**
     * URL delle foto della stanza nell'ordine del partner (path sul disco
     * public, come structures.gallery).
     *
     * @return list<string>
     */
    public function photoUrls(): array
    {
        $paths = array_values(array_unique(array_filter($this->photos ?? [], filled(...))));

        return array_map(fn (string $path): string => Storage::disk('public')->url($path), $paths);
    }

    /** Il gruppo (ospiti e animali) entra nella capienza della stanza. */
    public function fits(int $guests, int $animals): bool
    {
        return $guests <= $this->max_guests && $animals <= $this->max_animals;
    }

    /**
     * Ospiti entro max_guests: si tolgono prima bambini e ragazzi, gli adulti
     * restano almeno 1.
     *
     * @param  array<string, int>  $guests
     * @return array<string, int>
     */
    public function clampGuests(array $guests): array
    {
        foreach (['bambini', 'ragazzi', 'adulti'] as $key) {
            $min = $key === 'adulti' ? 1 : 0;

            while (array_sum($guests) > $this->max_guests && ($guests[$key] ?? 0) > $min) {
                $guests[$key]--;
            }
        }

        return $guests;
    }

    /**
     * Animali entro max_animals, togliendo dall'ultima specie.
     *
     * @param  array<string, int>  $animals
     * @return array<string, int>
     */
    public function clampAnimals(array $animals): array
    {
        foreach (array_reverse(array_keys($animals)) as $species) {
            while (array_sum($animals) > $this->max_animals && $animals[$species] > 0) {
                $animals[$species]--;
            }
        }

        return $animals;
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
