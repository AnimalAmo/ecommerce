<?php

namespace App\Models\Venue;

use Database\Factories\Venue\VenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory;

    protected $fillable = [
        'structure_draft_id',
        'name',
        'address',
        'map_img',
    ];
}
