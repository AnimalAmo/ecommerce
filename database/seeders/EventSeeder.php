<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\Venue\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $cascina = Venue::updateOrCreate(['name' => 'Cascina Brescia'], [
            'address' => 'Dario Boario Terme (BS), Italia',
            'map_img' => 'event-detail-map',
        ]);

        $miramare = Venue::updateOrCreate(['name' => 'Hotel Miramare'], [
            'address' => 'Via Roma 63, 30057, Viareggio, Italia',
            'map_img' => 'event-detail-map',
        ]);

        foreach ([...self::gridEvents(), ...self::homeEvents()] as $row) {
            $isActivity = ($row['type'] ?? 'event') === 'activity';

            Event::updateOrCreate(['slug' => $row['slug']], [
                'venue_id' => $isActivity ? $miramare->id : $cascina->id,
                'type' => $isActivity ? ProductType::Activity : ProductType::Event,
                'title' => $row['title'],
                'location' => $row['location'],
                'starts_at' => $row['starts_at'] ?? null,
                'ends_at' => $row['ends_at'] ?? null,
                'duration_days' => $row['duration_days'] ?? null,
                // Capienza demo (step 3): default 30, override per riga (8 = evento piccolo, 20 = escursioni).
                'max_participants' => $row['max_participants'] ?? 30,
                'price_cents' => $row['price_cents'] ?? null,
                'is_free' => $row['is_free'] ?? false,
                'img' => $row['img'],
                'hero_img' => $row['hero_img'] ?? ($isActivity ? 'activity-detail-hero' : (($row['is_free'] ?? false) ? 'free-event-detail-hero' : 'event-detail-hero')),
                'description' => XdCopy::LONG,
                'time_note' => XdCopy::SHORT,
                'venue_note' => XdCopy::SHORT,
                'position' => $row['position'] ?? null,
                'home_position' => $row['home_position'] ?? null,
            ])
                ->amenities()->sync(
                    AmenitySeeder::pivot(self::hotelAmenities()) + AmenitySeeder::pivot(self::animalAmenities())
                );
        }

        foreach (Event::all() as $event) {
            foreach (range(1, 5) as $position) {
                $event->faqs()->updateOrCreate(['position' => $position], [
                    'question' => XdCopy::FAQ_QUESTION,
                    'answer' => XdCopy::FAQ_ANSWER,
                ]);
            }
        }
    }

    /**
     * Le 12 card della griglia /eventi, VERBATIM da XD (ordine riga per riga).
     * Gli anni sono scelti perché il giorno della settimana derivato coincida con
     * quello stampato nel mock (es. "VEN, 18 GEN" → 2019). "Oggi" del mock = data
     * di seeding. duration_days null = weekend (riga durata assente in griglia).
     */
    private static function gridEvents(): array
    {
        return [
            ['position' => 1, 'slug' => 'brunch-pet-friendly', 'title' => 'Brunch Pet Friendly', 'location' => 'San Pellegrino, Italia', 'starts_at' => Carbon::today()->setTime(13, 30), 'ends_at' => Carbon::today()->setTime(16, 30), 'price_cents' => 2500, 'max_participants' => 8, 'img' => 'event-brunch-pet-friendly'],
            ['position' => 2, 'slug' => 'weekend-escursioni', 'title' => 'Weekend di escursioni', 'location' => 'Viareggio, Italia', 'type' => 'activity', 'price_cents' => 11800, 'max_participants' => 20, 'img' => 'event-weekend-escursioni'],
            ['position' => 3, 'slug' => 'festa-pet-friendly', 'title' => 'Festa Pet Friendly', 'location' => 'Milano, Italia', 'starts_at' => '2024-01-08 19:30', 'ends_at' => '2024-01-08 21:30', 'is_free' => true, 'img' => 'event-festa-pet-friendly'],
            ['position' => 4, 'slug' => 'raduno-cuccioli', 'title' => 'Raduno per cuccioli', 'location' => 'San Pellegrino, Italia', 'starts_at' => '2019-01-18 15:00', 'ends_at' => '2019-01-18 17:00', 'is_free' => true, 'img' => 'event-raduno-cuccioli'],
            ['position' => 5, 'slug' => 'weekend-mare', 'title' => 'Weekend al mare', 'location' => 'Genova, Italia', 'type' => 'activity', 'price_cents' => 21000, 'img' => 'event-weekend-mare'],
            ['position' => 6, 'slug' => 'giornata-piscina', 'title' => 'Giornata in piscina', 'location' => 'Viareggio, Italia', 'starts_at' => '2023-02-25 15:00', 'ends_at' => '2023-02-25 17:00', 'price_cents' => 800, 'img' => 'event-giornata-piscina'],
            ['position' => 7, 'slug' => 'weekend-bosco', 'title' => 'Weekend nel bosco', 'location' => 'Brianza, Italia', 'type' => 'activity', 'is_free' => true, 'img' => 'event-weekend-bosco'],
            ['position' => 8, 'slug' => 'puppy-yoga', 'title' => 'Puppy Yoga', 'location' => 'Sassari, Italia', 'starts_at' => '2025-04-18 15:00', 'ends_at' => '2025-04-18 17:00', 'price_cents' => 2500, 'img' => 'event-puppy-yoga'],
            ['position' => 9, 'slug' => 'vacanza-montagna', 'title' => 'Vacanza di relax in montagna', 'location' => 'Alpi, Italia', 'type' => 'activity', 'duration_days' => 5, 'price_cents' => 25000, 'img' => 'event-vacanza-montagna'],
            ['position' => 10, 'slug' => 'pomeriggio-addestramento', 'title' => 'Pomeriggio di addestramento', 'location' => 'Milano, Italia', 'starts_at' => '2024-05-25 15:00', 'ends_at' => '2024-05-25 17:00', 'img' => 'event-pomeriggio-addestramento'],
            ['position' => 11, 'slug' => 'puppy-yoga-milano', 'title' => 'Puppy Yoga', 'location' => 'Milano, Italia', 'starts_at' => '2022-05-30 15:30', 'ends_at' => '2022-05-30 17:30', 'price_cents' => 2500, 'img' => 'event-puppy-yoga-2'],
            ['position' => 12, 'slug' => 'giochi-cuccioli', 'title' => 'Giochi per cuccioli', 'location' => 'Sassari, Italia', 'starts_at' => '2021-06-18 15:00', 'ends_at' => '2021-06-18 17:00', 'is_free' => true, 'img' => 'event-giochi-cuccioli'],
        ];
    }

    /** Le 5 card eventi della home (set distinto dalla griglia nel mock XD). */
    private static function homeEvents(): array
    {
        return [
            ['home_position' => 1, 'slug' => 'passeggiata-a-cavallo', 'title' => 'Passeggiata a cavallo', 'location' => 'Genova, Italia', 'starts_at' => Carbon::today()->setTime(12, 30), 'ends_at' => Carbon::today()->setTime(14, 30), 'is_free' => true, 'img' => 'event-cavallo'],
            ['home_position' => 2, 'slug' => 'weekend-mare-fiesole', 'title' => 'Weekend al mare', 'location' => 'Fiesole (FI), Toscana', 'starts_at' => '2024-01-08 19:30', 'ends_at' => '2024-01-08 21:30', 'price_cents' => 2500, 'img' => 'event-mare'],
            ['home_position' => 3, 'slug' => 'esperienza-asini-fattoria', 'title' => 'Esperienza con gli asini in fattoria', 'location' => 'Manciano (GR), Toscana', 'starts_at' => Carbon::today()->setTime(15, 0), 'ends_at' => Carbon::today()->setTime(17, 0), 'price_cents' => 1800, 'img' => 'event-asini'],
            ['home_position' => 4, 'slug' => 'weekend-maneggio', 'title' => 'Weekend in maneggio', 'location' => 'Massa Lubrense (NA), Campania', 'starts_at' => '2019-01-18 15:00', 'ends_at' => '2019-01-18 17:00', 'price_cents' => 3500, 'img' => 'event-maneggio'],
            ['home_position' => 5, 'slug' => 'trekking-lago', 'title' => 'Trekking al lago', 'location' => 'Molveno (TN), Trentino', 'starts_at' => '2024-01-20 09:00', 'ends_at' => '2024-01-20 11:00', 'price_cents' => 1200, 'img' => 'event-cavallo'],
        ];
    }

    /** Colonna sinistra "Cosa è incluso" (variante evento: Pranzo al posto di Lavanderia). */
    private static function hotelAmenities(): array
    {
        return [
            'Aria condizionata negli spazi comuni' => true,
            'Pranzo' => true,
            'Ascensore' => true,
            'Wifi' => true,
            'Noleggio bici' => false,
            'Spa' => false,
        ];
    }

    /** Colonna destra "Cosa è incluso" (Dog sitter, non Pet sitting). */
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
