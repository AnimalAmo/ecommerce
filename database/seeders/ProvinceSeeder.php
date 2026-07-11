<?php

namespace Database\Seeders;

use App\Models\Region\Province;
use App\Models\Region\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Le 107 province italiane da database/locations-data/provinces.json
 * (stesso dataset dei progetti matsuri/storica) + aggancio alla regione
 * (ripartizione ISTAT): richiede RegionSeeder già eseguito. Idempotente.
 */
class ProvinceSeeder extends Seeder
{
    /** slug regione (RegionSeeder) => sigle provincia (ISTAT). */
    public const REGION_PROVINCES = [
        'piemonte' => ['TO', 'VC', 'NO', 'CN', 'AT', 'AL', 'BI', 'VB'],
        'valle-daosta' => ['AO'],
        'lombardia' => ['VA', 'CO', 'SO', 'MI', 'BG', 'BS', 'PV', 'CR', 'MN', 'LC', 'LO', 'MB'],
        'trentino-alto-adige' => ['BZ', 'TN'],
        'veneto' => ['VR', 'VI', 'BL', 'TV', 'VE', 'PD', 'RO'],
        'friuli-venezia-giulia' => ['UD', 'GO', 'TS', 'PN'],
        'liguria' => ['IM', 'SV', 'GE', 'SP'],
        'emilia-romagna' => ['PC', 'PR', 'RE', 'MO', 'BO', 'FE', 'RA', 'FC', 'RN'],
        'toscana' => ['MS', 'LU', 'PT', 'FI', 'LI', 'PI', 'AR', 'SI', 'GR', 'PO'],
        'umbria' => ['PG', 'TR'],
        'marche' => ['PU', 'AN', 'MC', 'AP', 'FM'],
        'lazio' => ['VT', 'RI', 'RM', 'LT', 'FR'],
        'abruzzo' => ['AQ', 'TE', 'PE', 'CH'],
        'molise' => ['CB', 'IS'],
        'campania' => ['CE', 'BN', 'NA', 'AV', 'SA'],
        'puglia' => ['FG', 'BA', 'TA', 'BR', 'LE', 'BT'],
        'basilicata' => ['PZ', 'MT'],
        'calabria' => ['CS', 'CZ', 'RC', 'KR', 'VV'],
        'sicilia' => ['TP', 'PA', 'ME', 'AG', 'CL', 'EN', 'CT', 'RG', 'SR'],
        'sardegna' => ['SS', 'NU', 'CA', 'OR', 'SU'],
    ];

    public function run(): void
    {
        $provinces = json_decode(File::get(database_path('locations-data/provinces.json')), true);

        $regionBySigla = [];
        $regions = Region::pluck('id', 'slug');
        foreach (self::REGION_PROVINCES as $slug => $sigle) {
            foreach ($sigle as $sigla) {
                $regionBySigla[$sigla] = $regions[$slug] ?? null;
            }
        }

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['short_name' => $province['short_name']],
                [
                    'name' => $province['name'],
                    'region_id' => $regionBySigla[$province['short_name']] ?? null,
                ],
            );
        }
    }
}
