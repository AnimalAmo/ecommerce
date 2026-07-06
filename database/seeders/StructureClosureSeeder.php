<?php

namespace Database\Seeders;

use App\Models\Structure\Structure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StructureClosureSeeder extends Seeder
{
    /**
     * Chiusure demo per struttura, offset in giorni RELATIVI a oggi (come le card
     * "Oggi" di EventSeeder: rilanciare il seeder le tiene fresche). Chiave = position
     * della struttura (gli slug del mock XD non sono unici). Idempotente via
     * updateOrCreate su [structure_id, date]; le chiusure passate restano innocue
     * (i calendari disabilitano comunque i giorni passati).
     *
     * @var array<int, int[]>
     */
    public const CLOSURES = [
        // Hotel Brescia (prima card): un range di 3 giorni per il demo del calendario.
        1 => [3, 4, 5],
        // Villaggio Turistico Tre Capitelli: giorno singolo.
        2 => [10],
        // Dog sitting (servizio): demo del giorno non disponibile nel picker a ore.
        4 => [7],
        // Lamasu W&R: weekend chiuso.
        8 => [14, 15],
    ];

    public function run(): void
    {
        foreach (self::CLOSURES as $position => $offsets) {
            $structure = Structure::where('position', $position)->firstOrFail();

            foreach ($offsets as $offset) {
                $structure->closures()->updateOrCreate([
                    'date' => Carbon::today()->addDays($offset)->toDateString(),
                ]);
            }
        }
    }
}
