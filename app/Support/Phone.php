<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberUtil;
use Locale;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * Unico punto in cui il progetto parla con laravel-phone: normalizzazione E.164,
 * formato di lettura e dati per il select dei prefissi (<x-phone-input>).
 */
final class Phone
{
    /** Paese assunto quando il numero arriva senza prefisso internazionale. */
    public const DEFAULT_COUNTRY = 'IT';

    /** Mercati principali della piattaforma: aprono il select, nell'ordine. */
    public const PRIORITY_COUNTRIES = ['IT', 'CH', 'FR', 'DE', 'ES', 'AT'];

    /** @var array<string, array<int, array{code: string, dial: string, flag: string, name: string}>> */
    private static array $countries = [];

    /**
     * Normalizza in E.164 (+393331234567). I valori non parsabili tornano
     * invariati: meglio un dato sporco che un dato perso.
     */
    public static function toE164(?string $number, string $country = self::DEFAULT_COUNTRY): ?string
    {
        $number = trim((string) $number);

        if ($number === '') {
            return null;
        }

        return self::parse($number, $country)?->formatE164() ?? $number;
    }

    /**
     * Regole condivise dai form. `international()` accetta qualunque paese purché
     * il numero porti il prefisso (è ciò che invia <x-phone-input>); `country()`
     * fa da rete per i numeri nazionali italiani scritti senza prefisso.
     *
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return ['string', Rule::phone()->international()->country(self::DEFAULT_COUNTRY)];
    }

    /** Formato di lettura: "+39 333 123 4567". */
    public static function format(?string $number): string
    {
        $number = trim((string) $number);

        if ($number === '') {
            return '';
        }

        return self::parse($number)?->formatInternational() ?? $number;
    }

    /**
     * Scompone un numero nelle due parti mostrate dal componente.
     *
     * @return array{country: string, national: string}
     */
    public static function split(?string $number): array
    {
        $number = trim((string) $number);

        if ($number === '') {
            return ['country' => self::DEFAULT_COUNTRY, 'national' => ''];
        }

        $phone = self::parse($number);

        if ($phone === null) {
            return ['country' => self::DEFAULT_COUNTRY, 'national' => $number];
        }

        // Dal formato internazionale, non da formatNational(): quest'ultimo
        // reintroduce il trunk prefix (lo 0 di "06 12 34 56 78" in Francia) che
        // in E.164 non esiste e che spezzerebbe la ricomposizione.
        $international = $phone->formatInternational();

        return [
            'country' => $phone->getCountry() ?? self::DEFAULT_COUNTRY,
            'national' => Str::contains($international, ' ')
                ? Str::after($international, ' ')
                : $international,
        ];
    }

    /**
     * Paesi per il select: mercati principali in testa, poi tutti gli altri in
     * ordine alfabetico sul nome tradotto.
     *
     * @return array<int, array{code: string, dial: string, flag: string, name: string}>
     */
    public static function countries(): array
    {
        $locale = app()->getLocale();

        if (isset(self::$countries[$locale])) {
            return self::$countries[$locale];
        }

        $util = PhoneNumberUtil::getInstance();
        $entries = [];

        foreach ($util->getSupportedRegions() as $code) {
            $dial = $util->getCountryCodeForRegion($code);

            if ($dial === 0) {
                continue;
            }

            $entries[$code] = [
                'code' => $code,
                'dial' => (string) $dial,
                'flag' => self::flag($code),
                'name' => self::countryName($code, $locale),
            ];
        }

        $priority = [];

        foreach (self::PRIORITY_COUNTRIES as $code) {
            if (isset($entries[$code])) {
                $priority[] = $entries[$code];
                unset($entries[$code]);
            }
        }

        $rest = array_values($entries);
        usort($rest, fn (array $a, array $b): int => Str::ascii($a['name']) <=> Str::ascii($b['name']));

        return self::$countries[$locale] = [...$priority, ...$rest];
    }

    /**
     * Mappa ISO → prefisso, letta da Alpine per ricomporre il numero.
     *
     * @return array<string, string>
     */
    public static function dialCodes(): array
    {
        return array_column(self::countries(), 'dial', 'code');
    }

    /** Bandiera come coppia di regional indicator symbols ("IT" → 🇮🇹). */
    public static function flag(string $code): string
    {
        $flag = '';

        foreach (str_split(mb_strtoupper($code)) as $char) {
            $flag .= mb_chr(ord($char) + 0x1F1A5, 'UTF-8');
        }

        return $flag;
    }

    /** Istanza laravel-phone solo se il numero è valido, altrimenti null. */
    private static function parse(string $number, string $country = self::DEFAULT_COUNTRY): ?PhoneNumber
    {
        try {
            $phone = new PhoneNumber($number, [$country]);

            return $phone->isValid() ? $phone : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function countryName(string $code, string $locale): string
    {
        if (! class_exists(Locale::class)) {
            return $code;
        }

        return Locale::getDisplayRegion('-'.$code, $locale) ?: $code;
    }
}
