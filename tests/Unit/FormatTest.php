<?php

namespace Tests\Unit;

use App\Support\Format;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    #[DataProvider('moneyProvider')]
    public function test_money_formats_cents_like_the_design(int $cents, string $expected): void
    {
        $this->assertSame($expected, Format::money($cents));
    }

    public static function moneyProvider(): array
    {
        // intl usa lo spazio unificatore (U+00A0) prima del simbolo €
        return [
            'whole amount drops decimals' => [21500, "215\u{A0}€"],
            'cart total' => [47600, "476\u{A0}€"],
            'decimals kept when present' => [11850, "118,50\u{A0}€"],
            'zero always extended' => [0, "0,00\u{A0}€"],
            'thousands separator' => [123456700, "1.234.567\u{A0}€"],
        ];
    }

    public function test_date_short_and_range(): void
    {
        $from = CarbonImmutable::create(2024, 2, 17);
        $to = CarbonImmutable::create(2024, 2, 22);

        $this->assertSame('17/02/2024', Format::dateShort($from));
        $this->assertSame('17/02/2024 - 22/02/2024', Format::dateRange($from, $to));
    }

    public function test_date_long_uses_italian_month_capitalized(): void
    {
        $this->assertSame('5 Ottobre 2023', Format::dateLong(CarbonImmutable::create(2023, 10, 5)));
    }

    public function test_event_time_matches_the_xd_display(): void
    {
        // 30/05/2022 era un lunedì, come nel mock 'LUN, 30 MAG ALLE 15:30'
        $this->assertSame('LUN, 30 MAG ALLE 15:30', Format::eventTime(CarbonImmutable::create(2022, 5, 30, 15, 30)));
    }
}
