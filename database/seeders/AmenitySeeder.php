<?php

namespace Database\Seeders;

use App\Models\Amenity\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /** Catalogo globale dei servizi ricorrenti nei mock XD, per gruppo. */
    public const AMENITIES = [
        Amenity::GROUP_HOTEL => [
            'Aria condizionata negli spazi comuni',
            'Lavanderia',
            'Ascensore',
            'Wifi',
            'Noleggio bici',
            'Spa',
            'Pranzo',
        ],
        Amenity::GROUP_ANIMAL => [
            'Pet sitting',
            'Dog sitter',
            'Servizio veterinario',
            'Omaggio di benvenuto',
            'Dog Beach nelle vicinanze',
            'Supplemento animali',
            'Piscina per cani',
        ],
    ];

    public function run(): void
    {
        foreach (self::AMENITIES as $group => $names) {
            foreach ($names as $name) {
                Amenity::updateOrCreate(['name' => $name], ['group' => $group]);
            }
        }
    }

    /**
     * Payload sync() per un set {nome => incluso} rispettando l'ordine riga del mock.
     *
     * @param  array<string, bool>  $set
     * @return array<int, array{included: bool, position: int}>
     */
    public static function pivot(array $set): array
    {
        $byName = Amenity::whereIn('name', array_keys($set))->pluck('id', 'name');

        $payload = [];
        $position = 1;

        foreach ($set as $name => $included) {
            $payload[$byName[$name]] = ['included' => $included, 'position' => $position++];
        }

        return $payload;
    }
}
