<?php

namespace App\Services\Partner;

use InvalidArgumentException;

/**
 * Cataloga le opzioni-chiave che il wizard partner salva su StructureDraft
 * (checkbox/radio: servizi, regole, servizi animali, tipologie...) e le
 * risolve nella label localizzata. Le chiavi su DB sono slug stabili; la
 * lingua la decide il lang file — così il dettaglio servizio è multilingua.
 *
 * Fino a P3 la mappa serviva solo a *rileggere* una bozza, quindi copriva una
 * parte degli slug: gli elenchi veri vivono nei @php delle viste del wizard,
 * duplicati fra hotel, attività e smartbox, e nessuno step li valida con un
 * `in:`. L'admin però scrive nella bozza di un altro partner e deve
 * whitelistare quello che salva, altrimenti un payload manomesso ci infila
 * slug che nessun publisher sa tradurre. Da qui `options()` e `slugs()`: la
 * stessa lista disegna i controlli e li valida, così non possono divergere.
 *
 * Le viste del wizard restano come sono (fuori perimetro P3): questa classe è
 * la fonte per l'admin, non ancora l'unico posto dove gli slug esistono.
 *
 * Questa costante è l'unico posto dove si aggiungono gruppi: un secondo task
 * che la riapre finisce per definire due volte la stessa lista con due nomi.
 */
class ServiceOptionLabels
{
    /** Mappa chiave draft => chiave lang, per gruppo. */
    private const MAPS = [
        // Colonna `services`. Gli stessi sette slug servono "cosa è incluso"
        // di attività e smartbox: stessa colonna, stesse label.
        'services' => [
            'aria_condizionata' => 'partner.hotel_services.svc_ac',
            'riscaldamento' => 'partner.hotel_services.svc_heating',
            'wifi' => 'partner.hotel_services.svc_wifi',
            'ricarica_elettrica' => 'partner.hotel_services.svc_ev',
            'tv' => 'partner.hotel_services.svc_tv',
            'piscina' => 'partner.hotel_services.svc_pool',
            'sauna' => 'partner.hotel_services.svc_sauna',
        ],
        // Colonna `additional_services` di struttura e attività. 'colazione',
        // 'pranzo' e 'cena' li rilegge StructurePublisher per le righe pasti.
        'additional' => [
            'nessuno' => 'partner.hotel_services.add_none',
            'colazione' => 'partner.hotel_services.add_breakfast',
            'pranzo' => 'partner.hotel_services.add_lunch',
            'cena' => 'partner.hotel_services.add_dinner',
            'altro' => 'partner.hotel_services.add_other',
        ],
        'rules' => [
            'vietato_fumare' => 'partner.hotel_services.rule_no_smoking',
            'vietato_feste' => 'partner.hotel_services.rule_no_parties',
        ],
        'animal_services' => [
            'nessuno' => 'partner.hotel_animal_services.opt_none',
            'omaggio' => 'partner.hotel_animal_services.opt_welcome',
            'pet_sitting' => 'partner.hotel_animal_services.opt_petsitting',
            'veterinario' => 'partner.hotel_animal_services.opt_vet',
            'area_animali' => 'partner.hotel_animal_services.opt_area',
            'altro' => 'partner.hotel_animal_services.opt_other',
        ],
        // Gruppo storico misto (struttura|attività|smartbox): lo usa
        // PartnerServiceDetail per la riga "Tipologia" senza sapere la
        // famiglia. Non si valida con questo — per quello ci sono i tre
        // gruppi sotto — ma gli mancava casa_vacanza, e il dettaglio del
        // partner mostrava lo slug grezzo.
        'type' => [
            'hotel' => 'partner.structure_type.hotel',
            'bb' => 'partner.structure_type.bb',
            'agriturismo' => 'partner.structure_type.agriturismo',
            'casa_vacanza' => 'partner.structure_type.casa_vacanza',
            'attivita' => 'partner.activity_type.attivita',
            'eventi' => 'partner.activity_type.eventi',
            'soggiorno' => 'partner.smartbox_type.soggiorno',
            'benessere' => 'partner.smartbox_type.benessere',
            'avventura' => 'partner.smartbox_type.avventura',
        ],
        'structure_type' => [
            'hotel' => 'partner.structure_type.hotel',
            'bb' => 'partner.structure_type.bb',
            'agriturismo' => 'partner.structure_type.agriturismo',
            'casa_vacanza' => 'partner.structure_type.casa_vacanza',
        ],
        'activity_type' => [
            'attivita' => 'partner.activity_type.attivita',
            'eventi' => 'partner.activity_type.eventi',
        ],
        'smartbox_type' => [
            'soggiorno' => 'partner.smartbox_type.soggiorno',
            'benessere' => 'partner.smartbox_type.benessere',
            'avventura' => 'partner.smartbox_type.avventura',
        ],
        // rooms[].type nelle strutture a camere.
        'room_type' => [
            'singola' => 'partner.hotel_rooms.type_single',
            'doppia' => 'partner.hotel_rooms.type_double',
            'tripla' => 'partner.hotel_rooms.type_triple',
            'suite' => 'partner.hotel_rooms.type_suite',
        ],
        // Casa vacanza: una riga sola, tipologia fittizia
        // (HotelRoomsForm::WHOLE_PROPERTY_TYPE). Gruppo a parte perché la
        // validazione è alternativa, non aggiuntiva: lo usa
        // StructureCreate::roomTypes().
        'room_type_whole' => [
            'intera_struttura' => 'partner.hotel_rooms.type_whole',
        ],
        // cancellation_when: stringhe, non interi (FamilyPublisher fa il cast).
        'cancellation' => [
            '30' => 'partner.hotel_cancellation.days_30',
            '15' => 'partner.hotel_cancellation.days_15',
            '7' => 'partner.hotel_cancellation.days_7',
            '1' => 'partner.hotel_cancellation.days_1',
        ],
        // smartbox_consent: il wizard salva 'si'/'no', non un boolean.
        'consent' => [
            'no' => 'partner.hotel_smartbox.no',
            'si' => 'partner.hotel_smartbox.yes',
        ],
        // Le quattro adesioni della struttura alle smartbox altrui
        // (colonna smartbox_types). NON si chiama 'smartbox_types'.
        'smartbox_consent' => [
            'tutta' => 'partner.hotel_smartbox.type_all',
            'pernottamento' => 'partner.hotel_smartbox.type_overnight',
            'benessere' => 'partner.hotel_smartbox.type_wellness',
            'avventura' => 'partner.hotel_smartbox.type_adventure',
        ],
        // Smartbox: riusa le colonne `services` e `additional_services` della
        // struttura con slug tutti suoi. Due gruppi separati, altrimenti
        // l'admin accetterebbe 'sauna' in una smartbox e 'cucina' in un hotel.
        'smartbox_amenities' => [
            'camera_da_letto' => 'partner.smartbox_offers.amenity_bedroom',
            'bagno' => 'partner.smartbox_offers.amenity_bathroom',
            'cucina' => 'partner.smartbox_offers.amenity_kitchen',
            'balcone' => 'partner.smartbox_offers.amenity_balcony',
            'terrazzo' => 'partner.smartbox_offers.amenity_terrace',
        ],
        'smartbox_additional' => [
            'piscina' => 'partner.smartbox_offers.add_pool',
            'spa' => 'partner.smartbox_offers.add_spa',
            'campo_da_tennis' => 'partner.smartbox_offers.add_tennis',
        ],
        'meals' => [
            'nessuno' => 'partner.smartbox_meals.meal_none',
            'colazione' => 'partner.smartbox_meals.meal_breakfast',
            'pranzo' => 'partner.smartbox_meals.meal_lunch',
            'cena' => 'partner.smartbox_meals.meal_dinner',
        ],
        'diets' => [
            'diabetico' => 'partner.smartbox_meals.diet_diabetic',
            'vegano' => 'partner.smartbox_meals.diet_vegan',
            'vegetariano' => 'partner.smartbox_meals.diet_vegetarian',
            'senza_glutine' => 'partner.smartbox_meals.diet_gluten_free',
            'senza_uova' => 'partner.smartbox_meals.diet_egg_free',
            'senza_lattosio' => 'partner.smartbox_meals.diet_lactose_free',
        ],
        'price_type' => [
            'pagamento' => 'partner.activity_cost.opt_paid',
            'gratuito' => 'partner.activity_cost.opt_free',
        ],
    ];

    /** Label localizzata di una singola chiave (fallback: la chiave stessa). */
    public static function label(string $group, ?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $langKey = self::MAPS[$group][$key] ?? null;

        return $langKey ? __($langKey) : $key;
    }

    /** Label localizzate di una lista di chiavi, nell'ordine dato. */
    public static function labels(string $group, ?array $keys): array
    {
        return array_map(fn ($key) => self::label($group, $key), $keys ?? []);
    }

    /**
     * Elenco completo di un gruppo, slug => label localizzata e nell'ordine
     * in cui il wizard lo mostra. Serve a DISEGNARE i controlli.
     *
     * Un gruppo sconosciuto è un errore di programmazione, non un elenco
     * vuoto: `Rule::in([])` rifiuterebbe ogni valore senza dire perché, e il
     * form sembrerebbe rotto invece che mal configurato.
     *
     * @return array<string, string>
     *
     * @throws InvalidArgumentException
     */
    public static function options(string $group): array
    {
        return array_map(fn (string $langKey): string => (string) __($langKey), self::map($group));
    }

    /**
     * I soli slug del gruppo. Serve a VALIDARE: `Rule::in(slugs($g))`.
     *
     * Esiste per un errore che è già capitato tre volte in questo piano:
     * `Rule::in(options($g))` itera i VALORI dell'array associativo, cioè le
     * etichette italiane — la regola accetterebbe 'Hotel' e rifiuterebbe
     * 'hotel', con un form che non salva mai e un test negativo verde per la
     * ragione sbagliata.
     *
     * Gli slug si castano a stringa perché PHP trasforma in intero ogni chiave
     * d'array che sia una stringa numerica: il gruppo `cancellation` ('30',
     * '15', '7', '1') uscirebbe come lista di int, mentre la colonna
     * `cancellation_when` della bozza è una stringa.
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    public static function slugs(string $group): array
    {
        return array_map(strval(...), array_keys(self::map($group)));
    }

    /**
     * I nomi dei gruppi esistenti, nell'ordine di dichiarazione. Lo usa il
     * test che impedisce i doppioni dentro MAPS: PHP non protesta se una
     * chiave è definita due volte, tiene l'ultima e basta.
     *
     * @return list<string>
     */
    public static function groupNames(): array
    {
        return array_keys(self::MAPS);
    }

    /** @return array<string, string> */
    private static function map(string $group): array
    {
        if (! isset(self::MAPS[$group])) {
            throw new InvalidArgumentException("Gruppo di opzioni sconosciuto: {$group}.");
        }

        return self::MAPS[$group];
    }
}
