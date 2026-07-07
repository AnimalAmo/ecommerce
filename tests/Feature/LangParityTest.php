<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LangParityTest extends TestCase
{
    /**
     * The translation files that must exist and match one-to-one between
     * lang/it and lang/en.
     *
     * @return array<int, array{0: string}>
     */
    public static function fileProvider(): array
    {
        return array_map(
            static fn (string $file) => [$file],
            [
                'auth',
                'cart',
                'catalog',
                'checkout',
                'format',
                'orders',
                'payment',
                'product-type',
                'profile',
                'validation',
            ],
        );
    }

    #[DataProvider('fileProvider')]
    public function test_english_keys_match_italian(string $file): void
    {
        $base = dirname(__DIR__, 2).'/lang';
        $itPath = "{$base}/it/{$file}.php";
        $enPath = "{$base}/en/{$file}.php";

        $this->assertFileExists($itPath, "Missing source file lang/it/{$file}.php");
        $this->assertFileExists($enPath, "Missing translated file lang/en/{$file}.php");

        $itKeys = $this->flatten(require $itPath);
        $enKeys = $this->flatten(require $enPath);

        $missing = array_diff($itKeys, $enKeys);
        $extra = array_diff($enKeys, $itKeys);

        $this->assertSame(
            [],
            array_values($missing),
            "lang/en/{$file}.php is missing keys: ".implode(', ', $missing),
        );
        $this->assertSame(
            [],
            array_values($extra),
            "lang/en/{$file}.php has extra keys: ".implode(', ', $extra),
        );
    }

    /**
     * Recursively flatten an array into dot-notation key paths.
     *
     * @param  array<string, mixed>  $array
     * @return array<int, string>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $path));
            } else {
                $keys[] = $path;
            }
        }

        sort($keys);

        return $keys;
    }
}
