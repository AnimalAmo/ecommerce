<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Invarianti dei file HTML generati da docx-to-html.py. Servono a far fallire
 * la suite se una riconversione futura reintroduce lo sporco di Word.
 */
class LegalContentTest extends TestCase
{
    /** @return array<int, array{0: string, 1: int}> slug e numero atteso di capitoli */
    public static function fileProvider(): array
    {
        return [
            [Page::TERMS_CUSTOMERS, 24],
            [Page::TERMS_SUPPLIERS, 17],
        ];
    }

    #[DataProvider('fileProvider')]
    public function test_the_italian_html_is_clean_and_complete(string $slug, int $chapters): void
    {
        $path = database_path("seeders/content/{$slug}.it.html");
        $this->assertFileExists($path);

        $html = file_get_contents($path);

        $this->assertSame($chapters, substr_count($html, '<h3 id='), 'Capitoli mancanti o duplicati');
        $this->assertStringNotContainsString('<span', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('class=', $html);
        $this->assertStringNotContainsString('<h1', $html, "L'h1 è del blade, non del corpo");
        $this->assertStringNotContainsString('Sommario', $html, 'Il sommario di Word va scartato');
        $this->assertMatchesRegularExpression('/<h3 id="[a-z0-9-]+">/', $html);
    }

    public function test_the_customer_document_keeps_its_two_sections(): void
    {
        $html = file_get_contents(database_path('seeders/content/'.Page::TERMS_CUSTOMERS.'.it.html'));

        $this->assertSame(2, substr_count($html, '<h2 id='));
        $this->assertStringContainsString('Sezione A', $html);
        $this->assertStringContainsString('Sezione B', $html);
    }

    public function test_the_supplier_chapters_are_numbered_like_the_source(): void
    {
        $html = file_get_contents(database_path('seeders/content/'.Page::TERMS_SUPPLIERS.'.it.html'));

        $this->assertStringContainsString('1. Premesse', $html);
        $this->assertStringContainsString('2. Definizioni', $html);
        $this->assertStringContainsString('17. Foro competente', $html);
    }

    public function test_external_links_open_safely(): void
    {
        foreach ([Page::TERMS_CUSTOMERS, Page::TERMS_SUPPLIERS] as $slug) {
            $html = file_get_contents(database_path("seeders/content/{$slug}.it.html"));

            preg_match_all('/<a href="http[^"]*"[^>]*>/', $html, $matches);

            foreach ($matches[0] as $anchor) {
                $this->assertStringContainsString('rel="noopener"', $anchor);
            }
        }
    }
}
