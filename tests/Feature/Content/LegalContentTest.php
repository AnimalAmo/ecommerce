<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Invarianti dei file HTML generati da docx-to-html.py. Servono a far fallire
 * la suite se una riconversione futura reintroduce lo sporco di Word.
 */
class LegalContentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array{0: string, 1: int}> slug e numero atteso di capitoli */
    public static function fileProvider(): array
    {
        return [
            [Page::TERMS_CUSTOMERS, 24],
            [Page::TERMS_SUPPLIERS, 17],
        ];
    }

    /** @return array<int, array{0: string}> slug dei due documenti */
    public static function slugProvider(): array
    {
        return [
            [Page::TERMS_CUSTOMERS],
            [Page::TERMS_SUPPLIERS],
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

    /**
     * Guardia contro la classe di difetto emersa durante lo sviluppo di
     * docx-to-html.py: uno stack di liste basato sulla profondità assoluta
     * di Word può produrre un `<ul>`/`<ol>` figlio diretto di un altro senza
     * un `<li>` di mezzo (HTML non valido, anche se ogni tag risulta
     * "chiuso"). Il conteggio di stringhe non lo intercetta: qui il file
     * viene caricato come frammento XML e la struttura viene ispezionata.
     */
    #[DataProvider('slugProvider')]
    public function test_the_lists_are_well_formed(string $slug): void
    {
        $html = file_get_contents(database_path("seeders/content/{$slug}.it.html"));

        $fragment = str_replace('<br>', '<br/>', $html);

        libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $loaded = $document->loadXML("<root>{$fragment}</root>");
        $errors = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue(
            $loaded,
            "HTML non ben formato in {$slug}: ".implode('; ', array_map(
                fn (\LibXMLError $error): string => trim($error->message), $errors
            ))
        );

        foreach (iterator_to_array($document->getElementsByTagName('li')) as $li) {
            $this->assertContains(
                $li->parentNode?->nodeName,
                ['ul', 'ol'],
                "<li> fuori da <ul>/<ol> in {$slug}: \"{$li->textContent}\""
            );
        }

        foreach (['ul', 'ol'] as $tag) {
            foreach (iterator_to_array($document->getElementsByTagName($tag)) as $list) {
                $this->assertNotContains(
                    $list->parentNode?->nodeName,
                    ['ul', 'ol'],
                    "<{$tag}> annidato direttamente in <{$list->parentNode?->nodeName}> senza <li> di mezzo, in {$slug}"
                );
            }
        }
    }

    public function test_the_seeder_loads_both_pages(): void
    {
        $this->seed(PageSeeder::class);

        $this->assertSame(2, Page::count());

        $customers = Page::where('slug', Page::TERMS_CUSTOMERS)->sole();
        $this->assertSame('Termini e condizioni', $customers->titleFor('it'));
        $this->assertStringContainsString('<h3 id=', $customers->bodyFor('it'));
        $this->assertNotNull($customers->last_updated_at);
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(PageSeeder::class);
        $this->seed(PageSeeder::class);

        $this->assertSame(2, Page::count());
    }
}
