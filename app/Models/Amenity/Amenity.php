<?php

namespace App\Models\Amenity;

use Database\Factories\Amenity\AmenityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    /** @use HasFactory<AmenityFactory> */
    use HasFactory;

    public const GROUP_HOTEL = 'hotel';

    public const GROUP_ANIMAL = 'animal';

    protected $fillable = [
        'name',
        'group',
    ];
}
