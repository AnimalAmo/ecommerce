<?php

namespace Tests\Unit;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Services\Pricing\BookingPricingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Quotazione server-side in integer cents: formule per famiglia (notti, ore per
 * eccesso, persone, supplemento animali, clamp minimo 1) sui valori dei seed XD.
 */
class PricingTest extends TestCase
{
    private BookingPricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');

        $this->pricing = new BookingPricingService;
    }

    #[DataProvider('structureProvider')]
    public function test_structure_prezzata_per_notte(array $attributes, array $options, int $expected): void
    {
        $structure = new Structure(['type' => ProductType::Structure, ...$attributes]);

        $this->assertSame($expected, $this->pricing->quote($structure, $options));
    }

    public static function structureProvider(): array
    {
        // hotel-brescia seed: 43 €/notte — l'XD mostra 5 notti = 215 €
        return [
            'cinque notti come XD' => [
                ['price_cents' => 4300, 'animal_supplement_cents' => 0],
                [
                    'animals' => ['cane' => 1],
                    'check_in' => '2026-08-01',
                    'check_out' => '2026-08-06',
                    'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
                ],
                21500,
            ],
            'una notte' => [
                ['price_cents' => 4300, 'animal_supplement_cents' => 0],
                ['animals' => ['cane' => 1], 'check_in' => '2026-08-01', 'check_out' => '2026-08-02'],
                4300,
            ],
            'supplemento per animale per notte' => [
                // 3 notti × 43 € + 5 € × 2 animali × 3 notti = 129 + 30 = 159 €
                ['price_cents' => 4300, 'animal_supplement_cents' => 500],
                ['animals' => ['cane' => 1, 'gatto' => 1], 'check_in' => '2026-08-01', 'check_out' => '2026-08-04'],
                15900,
            ],
            'senza animali il supplemento non pesa' => [
                ['price_cents' => 4300, 'animal_supplement_cents' => 500],
                ['animals' => [], 'check_in' => '2026-08-01', 'check_out' => '2026-08-03'],
                8600,
            ],
            'clamp a una notte con check-in e check-out coincidenti' => [
                // diffInDays = 0 ma si fattura comunque 1 notte (anche per il supplemento)
                ['price_cents' => 4300, 'animal_supplement_cents' => 200],
                ['animals' => ['cane' => 1], 'check_in' => '2026-08-01', 'check_out' => '2026-08-01'],
                4500,
            ],
        ];
    }

    #[DataProvider('serviceProvider')]
    public function test_service_prezzato_a_ore(array $attributes, array $options, int $expected): void
    {
        $service = new Structure(['type' => ProductType::Service, ...$attributes]);

        $this->assertSame($expected, $this->pricing->quote($service, $options));
    }

    public static function serviceProvider(): array
    {
        // dog-sitting seed: 12 €/ora — dalle 10:00 alle 16:00 sono 6 ore = 72 €
        return [
            'sei ore come il default del widget' => [
                ['price_cents' => 1200, 'animal_supplement_cents' => 0],
                ['animals' => ['cane' => 1], 'day' => '2026-08-01', 'time_from' => '10:00', 'time_to' => '16:00'],
                7200,
            ],
            'frazione di ora arrotondata per eccesso' => [
                // 90 minuti ⇒ 2 ore
                ['price_cents' => 1200, 'animal_supplement_cents' => 0],
                ['animals' => ['cane' => 1], 'day' => '2026-08-01', 'time_from' => '10:00', 'time_to' => '11:30'],
                2400,
            ],
            'supplemento per animale per ora' => [
                // 2 ore × 12 € + 3 € × 2 animali × 2 ore = 24 + 12 = 36 €
                ['price_cents' => 1200, 'animal_supplement_cents' => 300],
                ['animals' => ['cane' => 2], 'day' => '2026-08-01', 'time_from' => '10:00', 'time_to' => '12:00'],
                3600,
            ],
            'clamp a un\'ora con orari coincidenti' => [
                ['price_cents' => 1200, 'animal_supplement_cents' => 0],
                ['animals' => ['cane' => 1], 'day' => '2026-08-01', 'time_from' => '10:00', 'time_to' => '10:00'],
                1200,
            ],
        ];
    }

    #[DataProvider('personsProvider')]
    public function test_evento_e_attivita_prezzati_a_persona(array $attributes, array $options, int $expected): void
    {
        $event = new Event(['is_free' => false, ...$attributes]);

        $this->assertSame($expected, $this->pricing->quote($event, $options));
    }

    public static function personsProvider(): array
    {
        return [
            // La pagina evento non ha contatore: sempre 1 partecipante (25 € seed)
            'evento con un partecipante' => [
                ['type' => ProductType::Event, 'price_cents' => 2500],
                ['participants' => 1],
                2500,
            ],
            // Attività: somma ospiti — l'XD mostra ×2 persone = 236 €
            'attivita con due adulti come XD' => [
                ['type' => ProductType::Activity, 'price_cents' => 11800],
                ['animals' => ['cane' => 1], 'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0]],
                23600,
            ],
            'attivita somma tutte le fasce ospiti' => [
                ['type' => ProductType::Activity, 'price_cents' => 11800],
                ['animals' => ['cane' => 1], 'guests' => ['adulti' => 2, 'ragazzi' => 1, 'bambini' => 1]],
                47200,
            ],
            'participants ha precedenza sugli ospiti' => [
                ['type' => ProductType::Event, 'price_cents' => 2500],
                ['participants' => 3, 'guests' => ['adulti' => 1]],
                7500,
            ],
        ];
    }

    public function test_ospiti_negativi_non_generano_una_riga_a_prezzo_negativo(): void
    {
        // Regressione security: il client può idratare editGuests con valori
        // negativi (stepper Livewire senza #[Locked]). Senza clamp la riga
        // sarebbe negativa (11800 × -40) e sconterebbe il totale carrello.
        $activity = new Event(['type' => ProductType::Activity, 'price_cents' => 11800, 'is_free' => false]);

        $quote = $this->pricing->quote($activity, [
            'guests' => ['adulti' => -40, 'ragazzi' => 0, 'bambini' => 0],
        ]);

        // persons() clampa a minimo 1: la riga resta positiva, mai un credito.
        $this->assertSame(11800, $quote);
        $this->assertGreaterThan(0, $quote);
    }

    public function test_evento_gratuito_non_e_acquistabile(): void
    {
        // CTA Partecipa: mai nel carrello, niente fallback di prezzo
        $event = new Event(['type' => ProductType::Event, 'price_cents' => null, 'is_free' => true]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Questo prodotto non è acquistabile.');

        $this->pricing->quote($event, ['participants' => 1]);
    }

    public function test_evento_senza_prezzo_non_e_acquistabile(): void
    {
        // price_cents null equivale a gratuito anche senza flag is_free
        $event = new Event(['type' => ProductType::Event, 'price_cents' => null, 'is_free' => false]);

        $this->expectException(CartValidationException::class);
        $this->expectExceptionMessage('Questo prodotto non è acquistabile.');

        $this->pricing->quote($event, ['participants' => 1]);
    }

    public function test_smartbox_prezzo_flat_senza_prezzare_gli_animali(): void
    {
        $box = new SmartboxPackage(['type' => ProductType::Stay, 'price_cents' => 21500]);

        $this->assertSame(21500, $this->pricing->quote($box, ['animals' => ['cane' => 3]]));
    }
}
