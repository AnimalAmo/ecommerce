<?php

namespace Tests\Unit;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Services\Availability\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Disponibilità read-only (chiusure, date passate, intervalli/orari invalidi,
 * capienza massima): orologio finto con Carbon::setTestNow — primo uso nel
 * repo, ratificato — così "oggi" resta il 15/07/2026 in ogni asserzione.
 */
class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availability;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');

        // Oggi fisso: mercoledì 15/07/2026 a mezzogiorno
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));

        $this->availability = new AvailabilityService;
    }

    protected function tearDown(): void
    {
        // Reset dell'orologio finto prima dello smontaggio dell'app
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Structure: intervallo di notti ──────────────────────────────────────

    public function test_structure_disponibile_con_intervallo_valido_e_senza_chiusure(): void
    {
        $structure = Structure::factory()->create();

        // Check-in oggi stesso: il limite è "non nel passato"
        $this->availability->ensureAvailable($structure, [
            'check_in' => '2026-07-15',
            'check_out' => '2026-07-20',
        ]);

        $this->expectNotToPerformAssertions();
    }

    public function test_structure_con_check_in_passato_rifiutata(): void
    {
        $structure = Structure::factory()->create();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Seleziona una data futura.');

        $this->availability->ensureAvailable($structure, [
            'check_in' => '2026-07-14',
            'check_out' => '2026-07-20',
        ]);
    }

    #[DataProvider('invalidRangeProvider')]
    public function test_structure_con_intervallo_invalido_rifiutata(string $checkIn, string $checkOut): void
    {
        $structure = Structure::factory()->create();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('La data di check-out deve essere successiva al check-in.');

        $this->availability->ensureAvailable($structure, [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
    }

    public static function invalidRangeProvider(): array
    {
        return [
            'check-out uguale al check-in' => ['2026-07-20', '2026-07-20'],
            'check-out prima del check-in' => ['2026-07-20', '2026-07-18'],
        ];
    }

    #[DataProvider('closureInRangeProvider')]
    public function test_structure_con_chiusura_nell_intervallo_rifiutata(string $closedDate): void
    {
        $structure = Structure::factory()->create();
        $structure->closures()->create(['date' => $closedDate]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Le date selezionate non sono disponibili.');

        $this->availability->ensureAvailable($structure, [
            'check_in' => '2026-07-20',
            'check_out' => '2026-07-25',
        ]);
    }

    public static function closureInRangeProvider(): array
    {
        // Intervallo prenotato: [20, 25) — le notti vanno dal 20 al 24
        return [
            'chiusura sul check-in' => ['2026-07-20'],
            'chiusura a meta intervallo' => ['2026-07-22'],
            'chiusura sull\'ultima notte' => ['2026-07-24'],
        ];
    }

    public function test_chiusura_sul_giorno_di_check_out_non_blocca(): void
    {
        // La notte del check-out è esclusa: si dorme fino al giorno prima
        $structure = Structure::factory()->create();
        $structure->closures()->create(['date' => '2026-07-25']);

        $this->availability->ensureAvailable($structure, [
            'check_in' => '2026-07-20',
            'check_out' => '2026-07-25',
        ]);

        $this->expectNotToPerformAssertions();
    }

    public function test_chiusura_di_un_altra_struttura_non_blocca(): void
    {
        $structure = Structure::factory()->create();
        $other = Structure::factory()->create();
        $other->closures()->create(['date' => '2026-07-22']);

        $this->availability->ensureAvailable($structure, [
            'check_in' => '2026-07-20',
            'check_out' => '2026-07-25',
        ]);

        $this->expectNotToPerformAssertions();
    }

    // ── Service: giorno singolo + orari ─────────────────────────────────────

    public function test_service_disponibile_con_giorno_e_orari_validi(): void
    {
        $service = Structure::factory()->service()->create();

        $this->availability->ensureAvailable($service, [
            'day' => '2026-07-20',
            'time_from' => '10:00',
            'time_to' => '16:00',
        ]);

        $this->expectNotToPerformAssertions();
    }

    public function test_service_con_giorno_passato_rifiutato(): void
    {
        $service = Structure::factory()->service()->create();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Seleziona una data futura.');

        $this->availability->ensureAvailable($service, [
            'day' => '2026-07-14',
            'time_from' => '10:00',
            'time_to' => '16:00',
        ]);
    }

    public function test_service_con_giorno_chiuso_rifiutato(): void
    {
        $service = Structure::factory()->service()->create();
        $service->closures()->create(['date' => '2026-07-20']);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Il giorno selezionato non è disponibile.');

        $this->availability->ensureAvailable($service, [
            'day' => '2026-07-20',
            'time_from' => '10:00',
            'time_to' => '16:00',
        ]);
    }

    #[DataProvider('invalidTimesProvider')]
    public function test_service_con_orari_invalidi_rifiutato(string $timeFrom, string $timeTo): void
    {
        $service = Structure::factory()->service()->create();

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('L\'orario di fine deve essere successivo all\'inizio.');

        $this->availability->ensureAvailable($service, [
            'day' => '2026-07-20',
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
        ]);
    }

    public static function invalidTimesProvider(): array
    {
        return [
            'fine uguale all\'inizio' => ['10:00', '10:00'],
            'fine prima dell\'inizio' => ['16:00', '10:00'],
        ];
    }

    // ── Event/activity: data di inizio + capienza massima ───────────────────

    public function test_evento_gia_iniziato_rifiutato(): void
    {
        // Iniziato stamattina: conta l'ora, non solo il giorno
        $event = Event::factory()->make(['starts_at' => '2026-07-15 09:00:00', 'max_participants' => 30]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Seleziona una data futura.');

        $this->availability->ensureAvailable($event, ['participants' => 1]);
    }

    public function test_evento_disponibile_fino_alla_capienza_massima(): void
    {
        $event = Event::factory()->make(['starts_at' => '2026-07-20 15:30:00', 'max_participants' => 8]);

        // Il limite è inclusivo: 8 su 8 passa
        $this->availability->ensureAvailable($event, ['participants' => 8]);

        $this->expectNotToPerformAssertions();
    }

    public function test_evento_oltre_la_capienza_rifiutato(): void
    {
        $event = Event::factory()->make(['starts_at' => '2026-07-20 15:30:00', 'max_participants' => 8]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Non ci sono abbastanza posti disponibili.');

        $this->availability->ensureAvailable($event, ['participants' => 9]);
    }

    public function test_evento_disponibile_con_posti_residui_sufficienti(): void
    {
        // 5 posti già venduti su 8: 3 richiesti riempiono esattamente la capienza
        $event = Event::factory()->make([
            'starts_at' => '2026-07-20 15:30:00',
            'max_participants' => 8,
            'booked_participants' => 5,
        ]);

        $this->availability->ensureAvailable($event, ['participants' => 3]);

        $this->expectNotToPerformAssertions();
    }

    public function test_evento_conta_i_posti_gia_venduti_nella_capienza(): void
    {
        // 5 venduti su 8: 4 richiesti sforano anche se 4 < max_participants
        $event = Event::factory()->make([
            'starts_at' => '2026-07-20 15:30:00',
            'max_participants' => 8,
            'booked_participants' => 5,
        ]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Non ci sono abbastanza posti disponibili.');

        $this->availability->ensureAvailable($event, ['participants' => 4]);
    }

    public function test_capienza_illimitata_con_max_participants_null(): void
    {
        $event = Event::factory()->make(['starts_at' => '2026-07-20 15:30:00', 'max_participants' => null]);

        $this->availability->ensureAvailable($event, ['participants' => 999]);

        $this->expectNotToPerformAssertions();
    }

    public function test_attivita_somma_gli_ospiti_contro_la_capienza(): void
    {
        // Attività senza data puntuale (starts_at null): nessun vincolo temporale
        $activity = Event::factory()->activity(2)->make(['max_participants' => 3]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Non ci sono abbastanza posti disponibili.');

        $this->availability->ensureAvailable($activity, [
            'animals' => ['cane' => 1],
            'guests' => ['adulti' => 2, 'ragazzi' => 1, 'bambini' => 1],
        ]);
    }

    public function test_attivita_con_ospiti_negativi_rifiutata(): void
    {
        // Regressione security: editGuests idratato dal client con valori
        // negativi (bypass degli stepper) — la disponibilità deve rifiutarli
        // prima che il pricing produca una riga a prezzo negativo.
        $activity = Event::factory()->activity(2)->make(['max_participants' => 20]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Il numero di partecipanti selezionato non è valido.');

        $this->availability->ensureAvailable($activity, [
            'animals' => ['cane' => 1],
            'guests' => ['adulti' => -40, 'ragazzi' => 0, 'bambini' => 0],
        ]);
    }

    public function test_attivita_con_totale_ospiti_nullo_rifiutata(): void
    {
        $activity = Event::factory()->activity(2)->make(['max_participants' => 20]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Il numero di partecipanti selezionato non è valido.');

        $this->availability->ensureAvailable($activity, [
            'animals' => ['cane' => 1],
            'guests' => ['adulti' => 0, 'ragazzi' => 0, 'bambini' => 0],
        ]);
    }

    public function test_attivita_senza_data_puntuale_disponibile(): void
    {
        $activity = Event::factory()->activity(2)->make(['max_participants' => 20]);

        $this->availability->ensureAvailable($activity, [
            'animals' => ['cane' => 1],
            'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
        ]);

        $this->expectNotToPerformAssertions();
    }

    // ── Smartbox: sempre disponibile ────────────────────────────────────────

    public function test_smartbox_sempre_disponibile(): void
    {
        $box = SmartboxPackage::factory()->make(['type' => ProductType::Stay]);

        $this->availability->ensureAvailable($box, ['animals' => ['cane' => 5]]);

        $this->expectNotToPerformAssertions();
    }

    // ── closedDates: helper per i calendari ─────────────────────────────────

    public function test_closed_dates_elenca_solo_il_mese_richiesto_in_ordine(): void
    {
        $structure = Structure::factory()->create();
        // Inserite fuori ordine per verificare l'orderBy
        $structure->closures()->create(['date' => '2026-07-21']);
        $structure->closures()->create(['date' => '2026-07-20']);
        $structure->closures()->create(['date' => '2026-08-02']);

        // Le chiusure di altre strutture non entrano nel calendario
        Structure::factory()->create()->closures()->create(['date' => '2026-07-22']);

        $this->assertSame(
            ['2026-07-20', '2026-07-21'],
            $this->availability->closedDates($structure, 2026, 7)
        );
        $this->assertSame(
            ['2026-08-02'],
            $this->availability->closedDates($structure, 2026, 8)
        );
        $this->assertSame([], $this->availability->closedDates($structure, 2026, 9));
    }
}
