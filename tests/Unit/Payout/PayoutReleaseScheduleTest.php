<?php

namespace Tests\Unit\Payout;

use App\Models\OrderItem\OrderItem;
use App\Services\Payout\PayoutReleaseSchedule;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Il pavimento del rilascio, senza una data di prenotazione a coprirlo.
 *
 * Le Condizioni Fornitore (art. 8.9) promettono "almeno 14 giorni
 * dall'acquisto": se il calcolo azzera l'ora, un acquisto del pomeriggio
 * diventa bonificabile dopo tredici giorni e mezzo.
 */
class PayoutReleaseScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 9, 14, 16, 5, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_senza_data_di_prenotazione_il_rilascio_rispetta_i_quattordici_giorni_pieni(): void
    {
        $item = new OrderItem(['booked_from' => null]);

        $at = (new PayoutReleaseSchedule)->releaseAtFor($item);

        $this->assertTrue(
            $at->greaterThanOrEqualTo(Carbon::now()->addDays(14)),
            "Rilascio programmato per {$at}, cioè prima che i 14 giorni fossero trascorsi.",
        );
    }
}
