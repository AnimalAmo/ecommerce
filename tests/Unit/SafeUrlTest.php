<?php

namespace Tests\Unit;

use App\Support\SafeUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Il link del partner finisce in un href: l'escape di Blade non ferma uno
 * schema javascript: o data:, quindi passa solo ciò che il browser apre come
 * pagina web.
 */
class SafeUrlTest extends TestCase
{
    public function test_http_and_https_urls_pass_unchanged(): void
    {
        $this->assertSame('http://example.com/paga', SafeUrl::http('http://example.com/paga'));
        $this->assertSame('https://example.com/paga?x=1', SafeUrl::http('https://example.com/paga?x=1'));
        $this->assertSame('HTTPS://Example.com', SafeUrl::http('HTTPS://Example.com'));
    }

    public function test_surrounding_spaces_are_trimmed(): void
    {
        $this->assertSame('https://example.com', SafeUrl::http('  https://example.com '));
    }

    /** @return array<string, array{0: ?string}> */
    public static function unsafe(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript maiuscolo' => ['JAVASCRIPT:alert(1)'],
            'javascript con spazi iniziali' => ['  javascript:alert(1)'],
            'data' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            'vbscript' => ['vbscript:msgbox(1)'],
            'relativo allo schema' => ['//evil.example.com'],
            'senza schema' => ['example.com/paga'],
            'ftp' => ['ftp://example.com'],
            'null' => [null],
            'stringa vuota' => [''],
            'solo spazi' => ['   '],
        ];
    }

    #[DataProvider('unsafe')]
    public function test_anything_else_is_dropped(?string $url): void
    {
        $this->assertNull(SafeUrl::http($url));
    }
}
