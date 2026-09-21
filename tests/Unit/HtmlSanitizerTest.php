<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Il corpo di pagine e articoli esce sul sito con {!! !!}: HtmlSanitizer è
 * l'unica cosa fra l'editor del pannello e il browser di chi visita.
 */
class HtmlSanitizerTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function dangerous(): array
    {
        return [
            'script' => ['<p>Ciao</p><script>alert(1)</script>', '<p>Ciao</p>'],
            'style' => ['<style>body{display:none}</style><p>Ciao</p>', '<p>Ciao</p>'],
            'iframe' => ['<iframe src="https://evil.test"></iframe><p>Ciao</p>', '<p>Ciao</p>'],
            'event handler' => ['<p onclick="steal()">Ciao</p>', '<p>Ciao</p>'],
            'inline style' => ['<p style="position:fixed">Ciao</p>', '<p>Ciao</p>'],
            'javascript link' => ['<a href="javascript:alert(1)">Ciao</a>', 'Ciao'],
            'data link' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">Ciao</a>', 'Ciao'],
            'image with onerror' => ['<p>Ciao<img src="x" onerror="alert(1)"></p>', '<p>Ciao</p>'],
            'svg' => ['<svg onload="alert(1)"><circle /></svg><p>Ciao</p>', '<p>Ciao</p>'],
            'form' => ['<form action="https://evil.test"><input name="pwd"></form><p>Ciao</p>', '<p>Ciao</p>'],
            'comment' => ['<p>Ciao<!-- <script>x</script> --></p>', '<p>Ciao</p>'],
            'bad id' => ['<h2 id="x onmouseover=alert(1)">Titolo</h2>', '<h2>Titolo</h2>'],
            'escaped text stays text' => ['<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>'],
        ];
    }

    #[DataProvider('dangerous')]
    public function test_it_removes_what_could_run_on_the_site(string $html, string $expected): void
    {
        $this->assertSame($expected, HtmlSanitizer::clean($html));
    }

    public function test_it_keeps_the_formatting_of_the_editor(): void
    {
        $html = '<h2>Titolo</h2><p><strong>Grassetto</strong>, <em>corsivo</em>, <u>sottolineato</u>, <s>barrato</s>.<br>A capo.</p>'
            .'<ul><li>Uno</li><li>Due</li></ul><ol><li>Primo</li></ol><blockquote><p>Citazione</p></blockquote>'
            .'<p><a href="https://animalamo.it/chi-siamo">link</a> <a href="mailto:info@animalamo.it">mail</a> <a href="/contattaci">interno</a></p>';

        $this->assertSame($html, HtmlSanitizer::clean($html));
    }

    public function test_it_renames_the_equivalent_tags(): void
    {
        $this->assertSame(
            '<h2>Titolo</h2><p><strong>b</strong> <em>i</em> <s>del</s></p><h3>Sotto</h3>',
            HtmlSanitizer::clean('<h1>Titolo</h1><p><b>b</b> <i>i</i> <del>del</del></p><h4>Sotto</h4>'),
        );
    }

    public function test_harmless_unknown_tags_leave_their_text(): void
    {
        $this->assertSame('<p>Uno due</p>', HtmlSanitizer::clean('<div><p><span class="x">Uno</span> due</p></div>'));
    }

    public function test_a_new_tab_link_cannot_control_the_opener(): void
    {
        $this->assertSame(
            '<a href="https://ec.europa.eu/odr" target="_blank" rel="noopener noreferrer">ODR</a>',
            HtmlSanitizer::clean('<a href="https://ec.europa.eu/odr" target="_blank" rel="opener">ODR</a>'),
        );
    }

    public function test_an_empty_editor_counts_as_nothing(): void
    {
        $this->assertSame('', HtmlSanitizer::clean('<p></p>'));
        $this->assertSame('', HtmlSanitizer::clean('<p><br></p>'));
        $this->assertSame('', HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean('   '));
    }

    public function test_accented_text_survives(): void
    {
        $this->assertSame('<p>Perché l’età è «già» così</p>', HtmlSanitizer::clean('<p>Perché l’età è «già» così</p>'));
    }

    /** @return array<string, array{0: string}> */
    public static function legalDocuments(): array
    {
        $files = [];

        foreach (glob(__DIR__.'/../../database/seeders/content/*.html') as $path) {
            $files[basename($path)] = [$path];
        }

        foreach (glob(__DIR__.'/../../database/seeders/content/articles/*.html') as $path) {
            $files['articles/'.basename($path)] = [$path];
        }

        return $files;
    }

    /**
     * I documenti che abbiamo convertito noi (id sui titoli, liste lettera,
     * link esterni) devono attraversare il filtro senza perdere niente: è il
     * testo che la cliente riapre nel pannello. Cambia solo la forma delle
     * entità (&#x27; → ') e il rel dei link in nuova scheda, che si rafforza.
     */
    #[DataProvider('legalDocuments')]
    public function test_our_own_documents_pass_through_unchanged(string $path): void
    {
        $source = trim((string) file_get_contents($path));
        $expected = str_replace('rel="noopener"', 'rel="noopener noreferrer"', $source);

        $this->assertSame(
            html_entity_decode($expected, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            html_entity_decode(HtmlSanitizer::clean($source), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        );
    }
}
