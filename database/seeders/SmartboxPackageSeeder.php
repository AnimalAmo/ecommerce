<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\SmartboxPackage\SmartboxPackage;
use Illuminate\Database\Seeder;

class SmartboxPackageSeeder extends Seeder
{
    /** Le 12 card della griglia Smartbox, VERBATIM da XD (ordine riga per riga). */
    public const BOXES = [
        ['title' => 'Weekend di relax in Lombardia', 'slug' => 'relax-lombardia', 'type' => ProductType::Stay, 'audience' => 'Coppia', 'img' => 'smartbox-relax-lombardia'],
        ['title' => 'Weekend in Piemonte', 'slug' => 'piemonte', 'type' => ProductType::Stay, 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-piemonte'],
        ['title' => 'Weekend di relax in Sardegna', 'slug' => 'relax-sardegna', 'type' => ProductType::Wellness, 'audience' => 'Coppia', 'img' => 'smartbox-relax-sardegna'],
        ['title' => 'Weekend in Liguria', 'slug' => 'liguria', 'type' => ProductType::Adventure, 'audience' => 'Coppia', 'img' => 'smartbox-liguria'],
        ['title' => '1 settimana di relax in Sardegna', 'slug' => 'settimana-relax-sardegna', 'type' => ProductType::Stay, 'audience' => 'Coppia', 'img' => 'smartbox-settimana-relax-sardegna'],
        ['title' => '4 giorni al lago', 'slug' => 'lago', 'type' => ProductType::Adventure, 'audience' => 'Famiglia', 'img' => 'smartbox-lago'],
        ['title' => '1 settimana di avventure', 'slug' => 'avventure', 'type' => ProductType::Wellness, 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-avventure'],
        ['title' => '1 settimana in Sardegna', 'slug' => 'settimana-sardegna', 'type' => ProductType::Adventure, 'audience' => 'Famiglia', 'img' => 'smartbox-settimana-sardegna'],
        ['title' => 'Weekend di relax in Lombardia', 'slug' => 'relax-lombardia-2', 'type' => ProductType::Wellness, 'audience' => 'Coppia', 'img' => 'smartbox-relax-lombardia-2'],
        ['title' => 'Weekend sugli sci', 'slug' => 'sci', 'type' => ProductType::Adventure, 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-sci'],
        ['title' => 'Settimana bianca', 'slug' => 'bianca', 'type' => ProductType::Adventure, 'audience' => 'Famiglia', 'img' => 'smartbox-bianca'],
        ['title' => 'Weekend nella capitale', 'slug' => 'capitale', 'type' => ProductType::Stay, 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-capitale'],
    ];

    /** 215 € (dettaglio smartbox XD, uguale per ogni cofanetto nel mock). */
    public const PRICE_CENTS = 21500;

    public function run(): void
    {
        foreach (self::BOXES as $index => $row) {
            SmartboxPackage::updateOrCreate(['slug' => $row['slug']], [
                'type' => $row['type'],
                'title' => $row['title'],
                'audience' => $row['audience'],
                'audience_people' => 2,
                'price_cents' => self::PRICE_CENTS,
                'price_from_cents' => 0,
                'validity_months' => 12,
                'img' => $row['img'],
                'hero_img' => 'smartbox-dettaglio-hero',
                // Descrizioni vuote: copy in attesa della cliente (le sezioni sono guardate nel blade).
                'description' => '',
                'extended_description' => '',
                'general_info' => self::generalInfo(),
                'features' => self::features(),
                'position' => $index + 1,
            ])
                ->amenities()->sync(
                    AmenitySeeder::pivot(self::hotelAmenities()) + AmenitySeeder::pivot(self::animalAmenities())
                );
        }
    }

    private static function generalInfo(): array
    {
        return [
            ['icon' => 'calendar-return', 'title' => 'Cancellazione gratuita', 'lines' => []],
            ['icon' => 'coffee', 'title' => 'Colazione inclusa', 'lines' => ['Orario: 7:30-11:00']],
            ['icon' => 'lunch', 'title' => 'Pranzo e cena inclusi', 'lines' => ['Orario pranzo: 12:30-14:30', 'Orario cena: 19:30-21:30']],
        ];
    }

    private static function features(): array
    {
        return [
            ['icon' => 'bed', 'title' => 'Camera da letto', 'lines' => ['1 letto matrimoniale', '1 cuccia per il tuo cane']],
            ['icon' => 'lunch', 'title' => 'Cucina ed alimenti', 'lines' => ['Cucina attrezzata', '1 pasto per il tuo cane']],
            ['icon' => 'spa', 'title' => 'Accesso alla Spa', 'lines' => ['2 accessi alla Spa', 'Dog sitter']],
        ];
    }

    private static function hotelAmenities(): array
    {
        return [
            'Aria condizionata negli spazi comuni' => true,
            'Lavanderia' => true,
            'Ascensore' => true,
            'Wifi' => true,
            'Noleggio bici' => false,
            'Spa' => false,
        ];
    }

    private static function animalAmenities(): array
    {
        return [
            'Dog sitter' => true,
            'Servizio veterinario' => true,
            'Omaggio di benvenuto' => true,
            'Dog Beach nelle vicinanze' => true,
            'Supplemento animali' => false,
            'Piscina per cani' => false,
        ];
    }
}
