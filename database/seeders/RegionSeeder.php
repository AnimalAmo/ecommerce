<?php

namespace Database\Seeders;

use App\Models\Region\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /** Le 20 regioni italiane, slug identici alle rotte del template (AnimalHolidayRegion::REGION_NAMES). */
    public const REGIONS = [
        'abruzzo' => 'Abruzzo',
        'basilicata' => 'Basilicata',
        'calabria' => 'Calabria',
        'campania' => 'Campania',
        'emilia-romagna' => 'Emilia Romagna',
        'friuli-venezia-giulia' => 'Friuli-Venezia Giulia',
        'lazio' => 'Lazio',
        'liguria' => 'Liguria',
        'lombardia' => 'Lombardia',
        'marche' => 'Marche',
        'molise' => 'Molise',
        'piemonte' => 'Piemonte',
        'puglia' => 'Puglia',
        'sardegna' => 'Sardegna',
        'sicilia' => 'Sicilia',
        'toscana' => 'Toscana',
        'trentino-alto-adige' => 'Trentino-Alto Adige',
        'umbria' => 'Umbria',
        'valle-daosta' => 'Valle d’Aosta',
        'veneto' => 'Veneto',
    ];

    public function run(): void
    {
        foreach (self::REGIONS as $slug => $name) {
            Region::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
