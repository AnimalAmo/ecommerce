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

    public function test_event_time_uses_oggi_for_today(): void
    {
        $this->assertSame(
            'OGGI ALLE 13:30',
            Format::eventTime(CarbonImmutable::today()->setTime(13, 30))
        );
    }

    #[DataProvider('ratingProvider')]
    public function test_rating_drops_trailing_zero_like_the_cards(float $rating, string $expected): void
    {
        $this->assertSame($expected, Format::rating($rating));
    }

    public static function ratingProvider(): array
    {
        return [
            'half star kept' => [4.5, '4,5'],
            'whole rating without decimals' => [3.0, '3'],
            'five stars' => [5.0, '5'],
        ];
    }

    public function test_amount_always_shows_two_decimals_without_symbol(): void
    {
        $this->assertSame('25,00', Format::amount(2500));
        $this->assertSame('118,50', Format::amount(11850));
    }

    public function test_date_sentence_uses_lowercase_month(): void
    {
        $this->assertSame('23 febbraio 2023', Format::dateSentence(CarbonImmutable::create(2023, 2, 23)));
    }

    public function test_date_tile_matches_the_hero_tile(): void
    {
        $this->assertSame('8 Gen', Format::dateTile(CarbonImmutable::create(2024, 1, 8)));
    }

    public function test_event_time_sentence_matches_the_home_cards(): void
    {
        $this->assertSame(
            'Lun, 8 Gen alle ore 19:30',
            Format::eventTimeSentence(CarbonImmutable::create(2024, 1, 8, 19, 30))
        );
        $this->assertSame(
            'Oggi alle ore 12:30',
            Format::eventTimeSentence(CarbonImmutable::today()->setTime(12, 30))
        );
    }

    public function test_event_time_full_matches_the_detail_headline(): void
    {
        $this->assertSame(
            'Lunedì 8 Gennaio alle ore 19:30',
            Format::eventTimeFull(CarbonImmutable::create(2024, 1, 8, 19, 30))
        );
        $this->assertSame(
            'Oggi alle ore 13:30',
            Format::eventTimeFull(CarbonImmutable::today()->setTime(13, 30))
        );
    }

    public function test_event_time_range_matches_the_detail_info(): void
    {
        $this->assertSame(
            'Lunedì 8 Gennaio dalle ore 19:30 alle 21:30',
            Format::eventTimeRange(
                CarbonImmutable::create(2024, 1, 8, 19, 30),
                CarbonImmutable::create(2024, 1, 8, 21, 30)
            )
        );
        $this->assertSame(
            'Oggi dalle 13:30 alle 16:30',
            Format::eventTimeRange(
                CarbonImmutable::today()->setTime(13, 30),
                CarbonImmutable::today()->setTime(16, 30)
            )
        );
    }

    public function test_validity_matches_the_smartbox_detail(): void
    {
        $this->assertSame('1 anno', Format::validity(12));
        $this->assertSame('2 anni', Format::validity(24));
        $this->assertSame('6 mesi', Format::validity(6));
    }
}
