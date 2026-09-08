<?php

namespace App\Models\Region;

use App\Models\Structure\Structure;
use Database\Factories\Region\RegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'img',
        'position',
        'home_position',
        'structures_count',
    ];

    /**
     * Le strutture pubblicate nella regione. Serve a contarle davvero: la
     * colonna structures_count nasce dal mock XD ed è ferma ai numeri
     * dell'artboard, quindi con il catalogo vero direbbe il falso.
     *
     * @return HasMany<Structure, $this>
     */
    public function structures(): HasMany
    {
        return $this->hasMany(Structure::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
