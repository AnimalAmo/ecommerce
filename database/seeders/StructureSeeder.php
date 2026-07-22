<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Structure\Structure;
use Illuminate\Database\Seeder;

class StructureSeeder extends Seeder
{
    /**
     * Le 12 card della griglia regione, VERBATIM da XD (ordine riga per riga).
     * Slug ripetuti e "Dario Boario Terme" (refuso per Darfo) sono voluti: fedeltà al mock.
     */
    public const STRUCTURES = [
        ['type' => 'hotel', 'name' => 'Hotel Brescia', 'slug' => 'hotel-brescia', 'location' => 'Dario Boario Terme (BS), Italia', 'rating' => 4.5, 'img' => 'regione-hotel-brescia'],
        ['type' => 'hotel', 'name' => 'Villaggio Turistico Tre Capitelli', 'slug' => 'villaggio-turistico-tre-capitelli', 'location' => 'Tre Capitelli (BS), Italia', 'rating' => 3.0, 'img' => 'regione-villaggio-tre-capitelli'],
        ['type' => 'hotel', 'name' => 'Hotel Mantova Residence', 'slug' => 'hotel-mantova-residence', 'location' => 'Mantova, Italia', 'rating' => 3.5, 'img' => 'regione-hotel-mantova'],
        ['type' => 'servizi', 'name' => 'Dog sitting', 'slug' => 'dog-sitting', 'location' => 'Mantova, Italia', 'rating' => 4.5, 'img' => 'regione-dog-sitting'],
        ['type' => 'servizi', 'name' => 'Centro di addestramento', 'slug' => 'centro-di-addestramento', 'location' => 'Dario Boario Terme (BS), Italia', 'rating' => 4.5, 'img' => 'regione-centro-addestramento'],
        ['type' => 'servizi', 'name' => 'Pet sitting', 'slug' => 'pet-sitting', 'location' => 'Viareggio, Italia', 'rating' => 4.5, 'img' => 'regione-pet-sitting'],
        ['type' => 'hotel', 'name' => 'Hotel Mantova Residence', 'slug' => 'hotel-mantova-residence', 'location' => 'Mantova, Italia', 'rating' => 3.5, 'img' => 'regione-hotel-mantova-2'],
        ['type' => 'hotel', 'name' => 'Lamasu W&R', 'slug' => 'lamasu-wr', 'location' => 'San Felice del Benaco (BS) - Italia', 'rating' => 5.0, 'img' => 'regione-lamasu'],
        ['type' => 'hotel', 'name' => 'Hotel Brescia', 'slug' => 'hotel-brescia', 'location' => 'Dario Boario Terme (BS), Italia', 'rating' => 4.5, 'img' => 'regione-hotel-brescia-2'],
        ['type' => 'hotel', 'name' => 'Villaggio Turistico Tre Capitelli', 'slug' => 'villaggio-turistico-tre-capitelli', 'location' => 'Tre Capitelli (BS), Italia', 'rating' => 3.0, 'img' => 'regione-villaggio-tre-capitelli-2'],
        ['type' => 'hotel', 'name' => 'Hotel Mantova Residence', 'slug' => 'hotel-mantova-residence', 'location' => 'Mantova, Italia', 'rating' => 3.5, 'img' => 'regione-hotel-mantova-3'],
        ['type' => 'hotel', 'name' => 'Lamasu W&R', 'slug' => 'lamasu-wr', 'location' => 'San Felice del Benaco (BS) - Italia', 'rating' => 5.0, 'img' => 'regione-lamasu-2'],
    ];

    /** 43 € a notte (dettaglio struttura XD). */
    public const HOTEL_PRICE_CENTS = 4300;

    /** 12 € all'ora (dettaglio servizio XD). */
    public const SERVICE_PRICE_CENTS = 1200;

    public function run(): void
    {
        foreach (self::STRUCTURES as $index => $row) {
            $isHotel = $row['type'] === 'hotel';

            $structure = Structure::updateOrCreate(['position' => $index + 1], [
                'type' => $isHotel ? ProductType::Structure : ProductType::Service,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'location' => $row['location'],
                'rating' => $row['rating'],
                'price_cents' => $isHotel ? self::HOTEL_PRICE_CENTS : self::SERVICE_PRICE_CENTS,
                'price_from_cents' => 0,
                // 0 esplicito: i supplementi reali arriveranno dai partner (totali XD invariati).
                'animal_supplement_cents' => 0,
                'img' => $row['img'],
                'hero_img' => $isHotel ? 'struttura-hero' : 'servizio-hero',
                'map_img' => $isHotel ? 'struttura-mappa' : 'servizio-mappa',
                // Descrizione vuota: copy in attesa della cliente (la sezione è guardata nel blade).
                'description' => '',
                'general_info' => $isHotel ? self::hotelGeneralInfo() : self::serviceGeneralInfo(),
                'features' => $isHotel ? self::hotelFeatures() : null,
            ]);

            $structure->amenities()->sync(
                ($isHotel ? AmenitySeeder::pivot(self::hotelAmenities()) : [])
                + AmenitySeeder::pivot(self::animalAmenities())
            );

            // FAQ lorem rimosse (copy in attesa della cliente): delete esplicita così
            // anche i DB già seminati si ripuliscono al riseed (updateOrCreate non basta).
            $structure->faqs()->delete();
            $this->seedReviews($structure);
        }
    }

    /** 12 recensioni (contatore XD): le 3 dell'artboard ripetute in ciclo, prime 3 visibili. */
    private function seedReviews(Structure $structure): void
    {
        $samples = [
            ['rating' => 5.0, 'title' => 'Incredibile!', 'author_name' => 'Giulia Rossi', 'author_initials' => 'GR', 'avatar_color' => '#FF9F3E'],
            ['rating' => 4.5, 'title' => 'Molto bello', 'author_name' => 'Andrea Bianchi', 'author_initials' => 'AB', 'avatar_color' => '#FF9F3E'],
            ['rating' => 4.5, 'title' => 'Incredibile!', 'author_name' => 'Francesca Sogni', 'author_initials' => 'FS', 'avatar_color' => '#3E72FF'],
        ];

        foreach (range(1, 12) as $position) {
            $structure->reviews()->updateOrCreate(['position' => $position], [
                ...$samples[($position - 1) % 3],
                // Body vuoto: copy in attesa della cliente; le righe restano per contatore e media voti.
                'body' => '',
                'reviewed_at' => '2023-02-23',
            ]);
        }
    }

    /** @return array<string, bool> */
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

    /** @return array<string, bool> */
    private static function animalAmenities(): array
    {
        return [
            'Pet sitting' => true,
            'Servizio veterinario' => true,
            'Omaggio di benvenuto' => true,
            'Dog Beach nelle vicinanze' => true,
            'Supplemento animali' => false,
            'Piscina per cani' => false,
        ];
    }

    private static function hotelGeneralInfo(): array
    {
        return [
            ['icon' => 'calendar-return', 'title' => 'Cancellazione gratuita', 'lines' => []],
            ['icon' => 'coffee', 'title' => 'Colazione inclusa', 'lines' => ['Orario: 7:30-11:00']],
            ['icon' => 'lunch', 'title' => 'Pranzo e cena inclusi', 'lines' => ['Orario pranzo: 12:30-14:30', 'Orario cena: 19:30-21:30']],
        ];
    }

    private static function serviceGeneralInfo(): array
    {
        return [
            ['icon' => 'calendar-return', 'title' => 'Cancellazione gratuita', 'lines' => []],
            ['icon' => 'home', 'title' => 'Dog sitting a casa', 'lines' => []],
        ];
    }

    private static function hotelFeatures(): array
    {
        return [
            ['icon' => 'bed', 'title' => 'Camera da letto', 'lines' => ['1 letto matrimoniale', '1 cuccia per il tuo cane']],
            ['icon' => 'lunch', 'title' => 'Cucina ed alimenti', 'lines' => ['Cucina attrezzata', '1 pasto per il tuo cane']],
        ];
    }
}
