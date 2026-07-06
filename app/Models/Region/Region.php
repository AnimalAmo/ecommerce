<?php

namespace App\Models\Region;

use Database\Factories\Region\RegionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
