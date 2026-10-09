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
 * Dal 28/09/2026 (WP8) anche il wizard disegna da qui i gruppi `services`,
 * `additional`, `rules` e `animal_services`, e gli step li validano con
 * `Rule::in(slugs())`, filtrando alla rilettura gli slug che la bozza porta
 * ancora da prima: uno slug tolto da qui sparisce dal wizard e si perde alla
 * successiva risalvata dello step, non blocca il partner. Hanno ancora una
 * lista propria nella vista smartbox-offers, smartbox-meals, smartbox-type,
 * hotel-smartbox, structure-type e activity-type.
 *
 * Questa costante è l'unico posto dove si aggiungono gruppi: un secondo task
 * che la riapre finisce per definire due volte la stessa lista con due nomi.
 */
class ServiceOptionLabels
{
    /** Mappa chiave draft => chiave lang, per gruppo. */
    private const MAPS = [
        // Colonna `services`. Gli stessi slug servono "cosa è incluso" di
        // attività e smartbox: stessa colonna, stesse label.
        //
        // Ogni slug di questo gruppo è un servizio che la scheda pubblica può
        // mostrare: FamilyPublisher::AMENITY_MAP lo porta sulla sua voce del
        // catalogo amenity (AmenitySeeder). Uno slug nuovo qui senza la sua
        // riga là è una spunta che il partner mette e il cliente non vede.
        //
        // Gli ultimi tre in coda, per non spostare gli altri: sono voci del
        // catalogo amenity che nessuno slug raggiungeva, quindi non comparivano
        // mai su una scheda. La cliente, 26-27/09/2026: «Vorrei invece renderli
        // selezionabili dove pertinenti».
        'services' => [
            'aria_condizionata' => 'partner.hotel_services.svc_ac',
            'riscaldamento' => 'partner.hotel_services.svc_heating',
            'wifi' => 'partner.hotel_services.svc_wifi',
            'ricarica_elettrica' => 'partner.hotel_services.svc_ev',
            'tv' => 'partner.hotel_services.svc_tv',
            'piscina' => 'partner.hotel_services.svc_pool',
            'sauna' => 'partner.hotel_services.svc_sauna',
            'lavanderia' => 'partner.hotel_services.svc_laundry',
            'ascensore' => 'partner.hotel_services.svc_lift',
            'noleggio_bici' => 'partner.hotel_services.svc_bike_rental',
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
        // Colonna `animal_services`. Come per `services`, ogni slug tranne
        // 'nessuno' e 'altro' ha la sua voce del catalogo amenity.
        //
        // I quattro nuovi (cliente, 26-27/09/2026) stanno prima di 'altro' e
        // non in coda: 'altro' apre il campo di testo libero ed è l'ultima
        // scelta di ogni elenco del wizard. «Supplemento animali» non è un
        // servizio ma un costo, e la cliente lo tiene apposta: «per il cliente
        // è un'informazione importante».
        //
        // 'dog_sitter' esiste anche in `activity_category`: è un altro gruppo e
        // un'altra colonna (chi è il professionista, non cosa offre la
        // struttura), e il publisher non la legge per le amenity.
        'animal_services' => [
            'nessuno' => 'partner.hotel_animal_services.opt_none',
            'omaggio' => 'partner.hotel_animal_services.opt_welcome',
            'pet_sitting' => 'partner.hotel_animal_services.opt_petsitting',
            'veterinario' => 'partner.hotel_animal_services.opt_vet',
            'area_animali' => 'partner.hotel_animal_services.opt_area',
            'dog_sitter' => 'partner.hotel_animal_services.opt_dogsitter',
            'dog_beach' => 'partner.hotel_animal_services.opt_dog_beach',
            'supplemento_animali' => 'partner.hotel_animal_services.opt_surcharge',
            'piscina_cani' => 'partner.hotel_animal_services.opt_dog_pool',
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
        // Categorie professionali dell'attività, a scelta MULTIPLA (richiesta
        // della cliente, 26/09/2026: un maneggio può essere anche fattoria
        // didattica). Colonna `activity_categories` della bozza, JSON, quindi
        // qui vale come whitelist di ogni elemento della lista — non del
        // valore intero: `Rule::in(slugs('activity_category'))` va dentro
        // `activity_categories.*`, o la regola confronta un array con delle
        // stringhe e rifiuta tutto.
        'activity_category' => [
            'toelettatore' => 'partner.activity_category.toelettatore',
            'asilo_cani' => 'partner.activity_category.asilo_cani',
            'dog_sitter' => 'partner.activity_category.dog_sitter',
            'educatore_cinofilo' => 'partner.activity_category.educatore_cinofilo',
            'fotografo_pet' => 'partner.activity_category.fotografo_pet',
            'maneggio' => 'partner.activity_category.maneggio',
            'fattoria_didattica' => 'partner.activity_category.fattoria_didattica',
            'altro' => 'partner.activity_category.altro',
        ],
        // Tipologie di EVENTO, a scelta MULTIPLA (risposta della cliente,
        // 27/09/2026). Sono le gemelle di `activity_category` sull'altro ramo
        // dello stesso step: quelle dicono chi offre il servizio, queste che
        // cos'è l'evento. Gruppo separato e non un riuso, perché un partner
        // passa da `attivita` a `eventi` e le due liste di slug non si
        // sovrappongono. Colonna `event_categories` della bozza, JSON: la
        // regola va dentro `event_categories.*`, non sul valore intero.
        'event_category' => [
            'passeggiate_trekking' => 'partner.event_category.passeggiate_trekking',
            'educativi_esperti' => 'partner.event_category.educativi_esperti',
            'corsi_workshop' => 'partner.event_category.corsi_workshop',
            'sportivi' => 'partner.event_category.sportivi',
            'fattoria' => 'partner.event_category.fattoria',
            'fiere_mercatini' => 'partner.event_category.fiere_mercatini',
            'solidali_adozioni' => 'partner.event_category.solidali_adozioni',
            'speciali_pet_friendly' => 'partner.event_category.speciali_pet_friendly',
            'altro' => 'partner.event_category.altro',
        ],
        // Colonna `recurrence`: scelta SINGOLA, ed è solo un'etichetta per la
        // scheda. La cliente ha escluso la generazione delle date ripetute in
        // questa fase, quindi nessuno deve leggere 'ricorrente' come una regola
        // di ripetizione.
        'event_recurrence' => [
            'singolo' => 'partner.event_recurrence.singolo',
            'ricorrente' => 'partner.event_recurrence.ricorrente',
        ],
        // Colonna `booking_requirement`: un gruppo solo per attività ed eventi.
        // La cliente chiede la «possibilità di prenotazione» ai professionisti e
        // «obbligatoria o facoltativa» agli eventi: stessa informazione, tre
        // stati. Due gruppi quasi omonimi si contraddirebbero appena il partner
        // cambia ramo.
        'booking_requirement' => [
            'obbligatoria' => 'partner.booking_requirement.obbligatoria',
            'facoltativa' => 'partner.booking_requirement.facoltativa',
            'non_prevista' => 'partner.booking_requirement.non_prevista',
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

    /** Label localizzata di una singola chiave (fallback: la chiave stessa); $locale null = lingua corrente. */
    public static function label(string $group, ?string $key, ?string $locale = null): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $langKey = self::MAPS[$group][$key] ?? null;

        return $langKey ? __($langKey, [], $locale) : $key;
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
