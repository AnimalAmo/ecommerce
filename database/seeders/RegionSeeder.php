<?php

namespace Database\Seeders;

use App\Models\Region\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Le 20 regioni italiane: slug => [nome, img card, posizione griglia Animal Holiday
     * (ordine XD riga per riga, non alfabetico), posizione home (le 3 card del mock),
     * conteggio strutture del badge (dal mock, finché il catalogo per regione non è reale)].
     */
    public const REGIONS = [
        'lombardia' => ['Lombardia', 'holiday-lombardia', 1, null, 10],
        'lazio' => ['Lazio', 'holiday-lazio', 2, null, 10],
        'campania' => ['Campania', 'holiday-campania', 3, null, 7],
        'veneto' => ['Veneto', 'holiday-veneto', 4, 2, 5],
        'sicilia' => ['Sicilia', 'holiday-sicilia', 5, null, 5],
        'emilia-romagna' => ['Emilia Romagna', 'holiday-emilia-romagna', 6, null, 7],
        'piemonte' => ['Piemonte', 'holiday-piemonte', 7, null, 10],
        'puglia' => ['Puglia', 'holiday-puglia', 8, null, 5],
        'toscana' => ['Toscana', 'holiday-toscana', 9, null, 7],
        'calabria' => ['Calabria', 'holiday-calabria', 10, null, 10],
        'sardegna' => ['Sardegna', 'holiday-sardegna', 11, null, 10],
        'liguria' => ['Liguria', 'holiday-liguria', 12, 1, 10],
        'marche' => ['Marche', 'holiday-marche', 13, null, 10],
        'abruzzo' => ['Abruzzo', 'holiday-abruzzo', 14, null, 5],
        'friuli-venezia-giulia' => ['Friuli-Venezia Giulia', 'holiday-friuli', 15, null, 10],
        'trentino-alto-adige' => ['Trentino-Alto Adige', 'holiday-trentino', 16, 3, 7],
        'umbria' => ['Umbria', 'holiday-umbria', 17, null, 5],
        'basilicata' => ['Basilicata', 'holiday-basilicata', 18, null, 10],
        'molise' => ['Molise', 'holiday-molise', 19, null, 10],
        'valle-daosta' => ['Valle d’Aosta', 'holiday-valle-daosta', 20, null, 5],
    ];

    public function run(): void
    {
        foreach (self::REGIONS as $slug => [$name, $img, $position, $homePosition, $structuresCount]) {
            Region::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'img' => $img,
                'position' => $position,
                'home_position' => $homePosition,
                'structures_count' => $structuresCount,
            ]);
        }
    }
}
