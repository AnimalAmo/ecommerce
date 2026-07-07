<?php

namespace App\Models\Structure;

use Database\Factories\Structure\StructureClosureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Giorno di chiusura della struttura/servizio: una riga per data non prenotabile. */
class StructureClosure extends Model
{
    /** @use HasFactory<StructureClosureFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Solo data, senza ora: su sqlite (test) il formato di default 'Y-m-d H:i:s'
     * romperebbe il match dell'updateOrCreate idempotente del seeder.
     */
    protected $dateFormat = 'Y-m-d';

    protected $fillable = [
        'structure_id',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
