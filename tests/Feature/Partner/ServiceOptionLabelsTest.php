<?php

namespace Tests\Feature\Partner;

use App\Services\Partner\ServiceOptionLabels;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Gli slug che il wizard salva su StructureDraft. Dal 28/09/2026 (WP8) le viste
 * del wizard leggono da qui i gruppi services, additional, rules e
 * animal_services, e i loro step li validano con `in:`, come già il pannello
 * admin: questo test blocca la lista, perché uno slug tolto o rinominato qui
 * sparisce dal wizard e si perde alla successiva risalvata della bozza che lo
 * porta. Alcune viste smartbox e i tipi hanno ancora una lista propria.
 *
 * Niente RefreshDatabase: si leggono solo una costante e i lang file.
 */
class ServiceOptionLabelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    /** @return array<string, array{0: string, 1: list<string>}> */
    public static function optionGroups(): array
    {
        return [
            // Gli ultimi tre in coda: voci del catalogo amenity che nessuno
            // slug raggiungeva (cliente, 26-27/09/2026: «Vorrei invece renderli
            // selezionabili dove pertinenti»).
            'servizi struttura' => ['services', ['aria_condizionata', 'riscaldamento', 'wifi', 'ricarica_elettrica', 'tv', 'piscina', 'sauna', 'lavanderia', 'ascensore', 'noleggio_bici']],
            'servizi aggiuntivi' => ['additional', ['nessuno', 'colazione', 'pranzo', 'cena', 'altro']],
            'regole' => ['rules', ['vietato_fumare', 'vietato_feste']],
            // Le quattro della cliente (26-27/09/2026) prima di 'altro', che
            // apre il testo libero e resta l'ultima scelta.
            'servizi animali' => ['animal_services', ['nessuno', 'omaggio', 'pet_sitting', 'veterinario', 'area_animali', 'dog_sitter', 'dog_beach', 'supplemento_animali', 'piscina_cani', 'altro']],
            'tipologia struttura' => ['structure_type', ['hotel', 'bb', 'agriturismo', 'casa_vacanza']],
            'tipologia attività' => ['activity_type', ['attivita', 'eventi']],
            // Categorie professionali: otto voci confermate dalla cliente il
            // 26/09/2026, a scelta MULTIPLA. Tre accorpano sinonimi con la
            // barra (dog sitter/pet sitter, educatore/addestratore,
            // maneggio/centro equestre): uno slug per voce, non due, o il
            // filtro e la lettura si sdoppiano.
            'categorie professionali' => ['activity_category', ['toelettatore', 'asilo_cani', 'dog_sitter', 'educatore_cinofilo', 'fotografo_pet', 'maneggio', 'fattoria_didattica', 'altro']],
            'tipologia smartbox' => ['smartbox_type', ['soggiorno', 'benessere', 'avventura']],
            'tipologia stanza' => ['room_type', ['singola', 'doppia', 'tripla', 'suite']],
            'alloggio intero' => ['room_type_whole', ['intera_struttura']],
            'cancellazione' => ['cancellation', ['30', '15', '7', '1']],
            'consenso' => ['consent', ['no', 'si']],
            'adesione smartbox' => ['smartbox_consent', ['tutta', 'pernottamento', 'benessere', 'avventura']],
            'cosa troverai' => ['smartbox_amenities', ['camera_da_letto', 'bagno', 'cucina', 'balcone', 'terrazzo']],
            'aggiuntivi smartbox' => ['smartbox_additional', ['piscina', 'spa', 'campo_da_tennis']],
            'pasti' => ['meals', ['nessuno', 'colazione', 'pranzo', 'cena']],
            'diete' => ['diets', ['diabetico', 'vegano', 'vegetariano', 'senza_glutine', 'senza_uova', 'senza_lattosio']],
            'tipo di costo' => ['price_type', ['pagamento', 'gratuito']],
        ];
    }

    #[DataProvider('optionGroups')]
    public function test_every_group_lists_the_slugs_the_wizard_saves(string $group, array $slugs): void
    {
        // array_map(strval) sulle chiavi di options(): PHP converte in intero
        // ogni chiave d'array che sia una stringa numerica, e il gruppo
        // `cancellation` è fatto solo di quelle ('30', '15', '7', '1').
        $this->assertSame($slugs, array_map(strval(...), array_keys(ServiceOptionLabels::options($group))));
        $this->assertSame($slugs, ServiceOptionLabels::slugs($group), 'slugs() e options() devono raccontare la stessa lista');
    }

    #[DataProvider('optionGroups')]
    public function test_no_option_falls_back_to_its_own_slug(string $group, array $slugs): void
    {
        foreach (ServiceOptionLabels::options($group) as $slug => $label) {
            $this->assertNotSame($slug, $label, "manca la traduzione di {$group}.{$slug}");
            $this->assertNotSame('', trim($label));
            // __() su una chiave assente torna la chiave puntata, che è diversa
            // dallo slug: senza questo, una chiave lang mancante passerebbe il
            // test e uscirebbe grezza nel pannello ("partner.hotel_rooms.type_whole").
            $this->assertFalse(str_contains($label, '.'), "chiave lang mancante per {$group}.{$slug}");
        }
    }

    /**
     * L'elenco esatto dei gruppi. Serve a una cosa sola: un doppione dentro
     * MAPS non lo segnala PHP (l'ultimo vince, in silenzio) e non lo segnala
     * nessun altro test. Qui esce in rosso.
     */
    public function test_the_groups_are_exactly_these_and_there_are_no_duplicates(): void
    {
        $this->assertSame([
            'services', 'additional', 'rules', 'animal_services', 'type',
            'structure_type', 'activity_type', 'activity_category',
            // Tipologie di evento a scelta multipla, ricorrenza come etichetta e
            // stato della prenotazione: risposte della cliente del 27/09/2026.
            'event_category', 'event_recurrence', 'booking_requirement',
            'smartbox_type',
            'room_type', 'room_type_whole', 'cancellation',
            'consent', 'smartbox_consent',
            'smartbox_amenities', 'smartbox_additional',
            'meals', 'diets', 'price_type',
        ], ServiceOptionLabels::groupNames());
    }

    public function test_the_labels_are_the_italian_ones_of_the_wizard(): void
    {
        $this->assertSame('Wi-fi gratuito', ServiceOptionLabels::options('services')['wifi']);
        $this->assertSame('Omaggio di benvenuto', ServiceOptionLabels::options('animal_services')['omaggio']);
        $this->assertSame('Camera da letto', ServiceOptionLabels::options('smartbox_amenities')['camera_da_letto']);
        $this->assertSame('Senza lattosio', ServiceOptionLabels::options('diets')['senza_lattosio']);
        $this->assertSame('7 giorni', ServiceOptionLabels::options('cancellation')['7']);
        $this->assertSame('Gratuito', ServiceOptionLabels::options('price_type')['gratuito']);
        $this->assertSame('Tutta la struttura', ServiceOptionLabels::options('smartbox_consent')['tutta']);
        // Le tre voci accorpate vanno a video con la barra, come le ha scritte
        // la cliente: è una voce sola, non due.
        $this->assertSame('Dog sitter / Pet sitter', ServiceOptionLabels::options('activity_category')['dog_sitter']);
        $this->assertSame('Maneggio / Centro equestre', ServiceOptionLabels::options('activity_category')['maneggio']);
        $this->assertSame('Asilo per cani', ServiceOptionLabels::options('activity_category')['asilo_cani']);
    }

    public function test_the_professional_categories_exist_in_english_too(): void
    {
        // `lang/en` non è un vezzo: il catalogo è bilingue e una chiave mancante
        // uscirebbe grezza sulla scheda inglese, non vuota.
        app()->setLocale('en');

        foreach (ServiceOptionLabels::options('activity_category') as $slug => $label) {
            $this->assertNotSame($slug, $label, "manca la traduzione inglese di {$slug}");
            $this->assertFalse(str_contains($label, '.'), "chiave lang inglese mancante per {$slug}");
        }

        $this->assertSame('Other', ServiceOptionLabels::options('activity_category')['altro']);
    }

    /**
     * I due gruppi che diventano righe della scheda (WP8, 28/09/2026). La
     * parità delle chiavi la guarda LangParityTest; qui si guarda che lo step
     * inglese non stampi la chiave grezza né ricada sull'italiano per le voci
     * nuove.
     */
    public function test_the_services_and_the_animal_services_exist_in_english_too(): void
    {
        app()->setLocale('en');

        foreach (['services', 'animal_services'] as $group) {
            foreach (ServiceOptionLabels::options($group) as $slug => $label) {
                $this->assertNotSame($slug, $label, "manca la traduzione inglese di {$group}.{$slug}");
                $this->assertFalse(str_contains($label, '.'), "chiave lang inglese mancante per {$group}.{$slug}");
            }
        }

        $this->assertSame('Laundry', ServiceOptionLabels::options('services')['lavanderia']);
        $this->assertSame('Pet surcharge', ServiceOptionLabels::options('animal_services')['supplemento_animali']);
    }

    public function test_the_holiday_home_is_no_longer_shown_as_a_raw_slug(): void
    {
        // Il gruppo storico 'type' alimenta la riga "Tipologia" del dettaglio
        // servizio del partner, e casa_vacanza ci mancava: usciva 'casa_vacanza'.
        $this->assertSame('Casa vacanza', ServiceOptionLabels::label('type', 'casa_vacanza'));
    }

    public function test_an_unknown_group_is_refused_instead_of_returning_an_empty_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ServiceOptionLabels::options('servizi_animali');
    }

    public function test_an_unknown_group_is_refused_by_slugs_too(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ServiceOptionLabels::slugs('smartbox_types');
    }
}
