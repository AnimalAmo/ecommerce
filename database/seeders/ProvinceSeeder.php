<?php

namespace Database\Seeders;

use App\Models\Region\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Le 107 province italiane da database/locations-data/provinces.json
 * (stesso dataset dei progetti matsuri/storica). Idempotente.
 */
class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = json_decode(File::get(database_path('locations-data/provinces.json')), true);

        foreach ($provinces as $province) {
            Province::updateOrCreate(
                ['short_name' => $province['short_name']],
                ['name' => $province['name']],
            );
        }
    }
}
