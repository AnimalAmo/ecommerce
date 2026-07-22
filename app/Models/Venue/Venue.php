<?php

namespace App\Models\Venue;

use App\Models\Concerns\HasMapEmbed;
use Database\Factories\Venue\VenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory, HasMapEmbed;

    protected $fillable = [
        'structure_draft_id',
        'name',
        'address',
        'map_img',
    ];

    /** Query place per la Maps Embed: i venue partner hanno l'indirizzo derivato da città/provincia. */
    public function mapQuery(): ?string
    {
        // Il solo nome non basta: Google centrerebbe un omonimo qualunque — meglio nascondere la sezione.
        if (blank($this->address)) {
            return null;
        }

        return collect([$this->name, $this->address])->filter()->implode(', ');
    }

    /** Screenshot XD del detail evento/attività. */
    public function mapFallbackUrl(): ?string
    {
        return filled($this->map_img) ? asset('img/xd/'.$this->map_img.'.jpg') : null;
    }
}
