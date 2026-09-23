<?php

namespace Tests\Feature\Partner;

use App\Services\Partner\ServiceOptionLabels;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Gli slug che il wizard salva su StructureDraft vivono, oggi, nei @php delle
 * viste: gli stessi sette servizi sono scritti in hotel-services,
 * activity-included e smartbox-included. L'admin li deve validare, non solo
 * disegnare, quindi questo test blocca la lista: se una vista aggiunge
 * un'opzione senza passare di qui, l'admin la rifiuterebbe in silenzio.
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
            'servizi struttura' => ['services', ['aria_condizionata', 'riscaldamento', 'wifi', 'ricarica_elettrica', 'tv', 'piscina', 'sauna']],
            'servizi aggiuntivi' => ['additional', ['nessuno', 'colazione', 'pranzo', 'cena', 'altro']],
            'regole' => ['rules', ['vietato_fumare', 'vietato_feste']],
            'servizi animali' => ['animal_services', ['nessuno', 'omaggio', 'pet_sitting', 'veterinario', 'area_animali', 'altro']],
            'tipologia struttura' => ['structure_type', ['hotel', 'bb', 'agriturismo', 'casa_vacanza']],
            'tipologia attività' => ['activity_type', ['attivita', 'eventi']],
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
            'structure_type', 'activity_type', 'smartbox_type',
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
