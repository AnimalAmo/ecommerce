<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Titoli e descrizioni del catalogo sono diventati translatable (spatie, JSON in
 * colonna) con la pubblicazione partner (4b2ddd3): i DB migrati prima — la demo —
 * hanno ancora testo piano, e json_extract() esplode su testo non-JSON alla prima
 * ricerca "Dove" (SQLSTATE 22032). I valori legacy vengono avvolti in {"it": …},
 * l'unico locale dei contenuti pre-conversione. Idempotente: un valore è "già
 * convertito" solo se decodifica in array/oggetto JSON, quindi i JSON validi
 * restano intatti e un testo piano numerico ("123") viene comunque avvolto.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> tabella → colonne translatable, come dichiarate nei model */
    private const COLUMNS = [
        'events' => ['title', 'description'],
        'smartbox_packages' => ['title', 'description', 'extended_description'],
        'structures' => ['name', 'description'],
        'structure_drafts' => ['name', 'description', 'detailed_description', 'meeting_point', 'additional_other', 'animal_services_other'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->select('id', $column)
                    ->whereNotNull($column)
                    ->orderBy('id')
                    ->chunkById(200, function ($rows) use ($table, $column): void {
                        foreach ($rows as $row) {
                            if (is_array(json_decode($row->{$column}, true))) {
                                continue;
                            }

                            DB::table($table)->where('id', $row->id)->update([
                                $column => json_encode(['it' => $row->{$column}], JSON_UNESCAPED_UNICODE),
                            ]);
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        // Normalizzazione dati una tantum: non c'è uno stato precedente da ripristinare.
    }
};
