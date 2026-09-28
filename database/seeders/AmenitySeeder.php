<?php

namespace Database\Seeders;

use App\Models\Amenity\Amenity;
use App\Services\Partner\Publishing\FamilyPublisher;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Catalogo globale dei servizi, per gruppo. Nato dai mock XD, oggi è la
     * fonte di tre cose:
     *
     *  - le righe della tabella `amenities` (questo seeder, e la migrazione
     *    2026_09_28_140001 per i database già seminati: il seeder al deploy
     *    non gira);
     *  - il nome che il cliente legge sulla scheda (HasAmenities::amenityRows());
     *  - l'ORDINE riga dentro il gruppo, che FamilyPublisher::amenityPivot()
     *    legge da qui e non dall'id: nel database del cliente le voci aggiunte dopo la
     *    prima semina hanno id più alti, e finirebbero in coda qualunque posto
     *    abbiano in questa lista.
     *
     * Ogni voce ha il suo slug del wizard in FamilyPublisher::AMENITY_MAP, e
     * viceversa. Fino al 28/09/2026 la mappa ne raggiungeva sette su quattordici
     * (otto slug, sauna e spa sulla stessa Spa; le sette non raggiunte sono
     * quelle che la cliente ha chiesto di rendere selezionabili il 26-27/09/2026)
     * e sei slug spuntabili non avevano una voce qui: Riscaldamento, Ricarica
     * auto elettriche, TV, Piscina, Area dedicata agli animali e Campo da tennis
     * (aggiuntivi smartbox). Le sei sono inserite accanto alle voci con cui si leggono
     * (clima, tecnologia in camera, mobilità, benessere, spazi per l'animale),
     * senza spostare le quattordici fra loro.
     *
     * «Piscina» e «Piscina per cani» sono due voci di due gruppi, non una.
     * Rinominare una voce qui NON rinomina la riga a database: updateOrCreate
     * cerca per nome e ne creerebbe una seconda.
     */
    public const AMENITIES = [
        Amenity::GROUP_HOTEL => [
            'Aria condizionata negli spazi comuni',
            'Riscaldamento',
            'Lavanderia',
            'Ascensore',
            'Wifi',
            'TV',
            'Noleggio bici',
            'Ricarica auto elettriche',
            'Piscina',
            'Spa',
            'Campo da tennis',
            'Pranzo',
        ],
        Amenity::GROUP_ANIMAL => [
            'Pet sitting',
            'Dog sitter',
            'Servizio veterinario',
            'Omaggio di benvenuto',
            'Area dedicata agli animali',
            'Dog Beach nelle vicinanze',
            'Supplemento animali',
            'Piscina per cani',
        ],
    ];

    public function run(): void
    {
        self::upsertCatalog();
    }

    /**
     * Crea le voci che mancano e riporta al gruppo giusto quelle che ci sono.
     * Idempotente: la chiamano sia il seeder sia la migrazione dei dati
     * 2026_09_28_140001, così la lista resta una.
     *
     * @return list<string> i nomi delle voci create adesso
     */
    public static function upsertCatalog(): array
    {
        $created = [];

        foreach (self::AMENITIES as $group => $names) {
            foreach ($names as $name) {
                if (Amenity::updateOrCreate(['name' => $name], ['group' => $group])->wasRecentlyCreated) {
                    $created[] = $name;
                }
            }
        }

        return $created;
    }

    /**
     * Payload sync() per un set {nome => incluso} rispettando l'ordine riga del mock.
     *
     * Solo per i seeder del catalogo demo, che scelgono a mano quali voci
     * mostrare e in che ordine. Le schede dei partner passano da
     * {@see FamilyPublisher::amenityPivot()}.
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
