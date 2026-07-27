<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Normalizzazione E.164 e scomposizione prefisso/numero: è il solo punto in cui
 * il progetto parla con laravel-phone, quindi le regole vivono tutte qui.
 */
class PhoneTest extends TestCase
{
    #[DataProvider('italianNumbers')]
    public function test_normalizes_italian_numbers_to_e164(string $input, string $expected): void
    {
        $this->assertSame($expected, Phone::toE164($input));
    }

    public static function italianNumbers(): array
    {
        return [
            'cellulare senza spazi' => ['3331234567', '+393331234567'],
            'cellulare con spazi' => ['340 5738920', '+393405738920'],
            'cellulare già internazionale' => ['+39 333 123 4567', '+393331234567'],
            'fisso con prefisso urbano' => ['06 45678901', '+390645678901'],
        ];
    }

    public function test_normalizes_foreign_numbers_from_their_own_prefix(): void
    {
        // Numero francese: il paese si deduce dal +33, non dal default italiano.
        $this->assertSame('+33612345678', Phone::toE164('+33 6 12 34 56 78'));
    }

    public function test_uses_the_given_country_when_the_number_is_national(): void
    {
        $this->assertSame('+33612345678', Phone::toE164('06 12 34 56 78', 'FR'));
    }

    public function test_returns_null_for_empty_values(): void
    {
        $this->assertNull(Phone::toE164(null));
        $this->assertNull(Phone::toE164(''));
        $this->assertNull(Phone::toE164('   '));
    }

    /** Un numero non parsabile va conservato: meglio un dato sporco che un dato perso. */
    public function test_keeps_unparsable_values_untouched(): void
    {
        $this->assertSame('da chiedere', Phone::toE164(' da chiedere '));
    }

    public function test_formats_for_display(): void
    {
        $this->assertSame('+39 333 123 4567', Phone::format('+393331234567'));
        $this->assertSame('', Phone::format(null));
        $this->assertSame('da chiedere', Phone::format('da chiedere'));
    }

    public function test_splits_an_e164_number_into_country_and_national_part(): void
    {
        $this->assertSame(
            ['country' => 'IT', 'national' => '333 123 4567'],
            Phone::split('+393331234567')
        );
    }

    /** In Francia il formato nazionale reintroduce lo 0 iniziale, che in E.164 non esiste. */
    public function test_split_drops_the_trunk_prefix(): void
    {
        $this->assertSame(
            ['country' => 'FR', 'national' => '6 12 34 56 78'],
            Phone::split('+33612345678')
        );
    }

    public function test_split_falls_back_to_the_default_country(): void
    {
        $this->assertSame(['country' => 'IT', 'national' => ''], Phone::split(null));
        $this->assertSame(['country' => 'IT', 'national' => 'da chiedere'], Phone::split('da chiedere'));
    }

    public function test_country_list_opens_with_the_priority_markets(): void
    {
        $countries = Phone::countries();
        $codes = array_column($countries, 'code');

        $this->assertSame(Phone::PRIORITY_COUNTRIES, array_slice($codes, 0, count(Phone::PRIORITY_COUNTRIES)));
        $this->assertContains('HR', $codes, 'la lista deve restare completa, non solo i mercati principali');
        $this->assertSame(count($codes), count(array_unique($codes)), 'nessun paese duplicato');

        $italy = $countries[0];
        $this->assertSame('39', $italy['dial']);
        $this->assertSame('🇮🇹', $italy['flag']);
        $this->assertNotSame('', $italy['name']);
    }

    public function test_dial_codes_map_every_country_of_the_list(): void
    {
        $dials = Phone::dialCodes();

        $this->assertSame('39', $dials['IT']);
        $this->assertSame('41', $dials['CH']);
        $this->assertCount(count(Phone::countries()), $dials);
    }
}
