<?php

namespace Tests\Unit;

use App\Services\Commerce\CommissionCalculator;
use Tests\TestCase;

/**
 * Regola commissionale (decisione 4 del 2026-09-09): sotto la soglia nessuna
 * provvigione — e "nessuna" significa null, non zero, perché Stripe rifiuta
 * application_fee_amount = 0 con invalid_request_error.
 */
class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new CommissionCalculator;
    }

    public function test_sotto_soglia_non_e_dovuta_provvigione(): void
    {
        $this->assertNull($this->calculator->feeCentsFor(4999));
    }

    public function test_la_soglia_include_il_proprio_estremo(): void
    {
        $this->assertSame(500, $this->calculator->feeCentsFor(5000));
    }

    public function test_sopra_soglia_e_il_dieci_percento(): void
    {
        $this->assertSame(1234, $this->calculator->feeCentsFor(12345));
    }

    public function test_i_centesimi_si_arrotondano_verso_il_basso(): void
    {
        // 5001 * 10% = 500,1 cent: mai chiedere al partner più del 10%.
        $this->assertSame(500, $this->calculator->feeCentsFor(5001));
    }

    public function test_le_regole_del_singolo_partner_hanno_la_precedenza(): void
    {
        $this->assertNull($this->calculator->feeCentsFor(10000, rateBp: 1500, minCents: 20000));
        $this->assertSame(1500, $this->calculator->feeCentsFor(10000, rateBp: 1500, minCents: 5000));
    }

    public function test_aliquota_zero_non_produce_una_fee_a_zero(): void
    {
        // Un partner in franchigia totale: il parametro va omesso, non messo a zero.
        $this->assertNull($this->calculator->feeCentsFor(10000, rateBp: 0));
    }
}
