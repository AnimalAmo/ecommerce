<?php

namespace Tests\Unit;

use App\Models\Page\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_and_body_are_translatable(): void
    {
        $page = Page::create([
            'slug' => Page::TERMS_CUSTOMERS,
            'title' => ['it' => 'Termini e condizioni', 'en' => 'Terms and conditions'],
            'body' => ['it' => '<p>Testo italiano</p>', 'en' => '<p>English text</p>'],
        ]);

        $this->assertSame('Termini e condizioni', $page->titleFor('it'));
        $this->assertSame('<p>English text</p>', $page->bodyFor('en'));
    }

    public function test_a_missing_translation_falls_back_to_italian(): void
    {
        $page = Page::create([
            'slug' => Page::TERMS_SUPPLIERS,
            'title' => ['it' => 'Condizioni fornitore'],
            'body' => ['it' => '<p>Solo italiano</p>'],
        ]);

        // Il testo di partenza è italiano: una pagina senza traduzione non
        // deve uscire vuota, altrimenti /en/... mostra un corpo bianco.
        $this->assertSame('Condizioni fornitore', $page->titleFor('en'));
        $this->assertSame('<p>Solo italiano</p>', $page->bodyFor('en'));
    }

    public function test_last_updated_at_is_a_date_and_may_be_null(): void
    {
        $page = Page::create([
            'slug' => 'test',
            'title' => ['it' => 'T'],
            'body' => ['it' => '<p>B</p>'],
            'last_updated_at' => '2026-08-26',
        ]);

        $this->assertSame('26/08/2026', $page->last_updated_at->format('d/m/Y'));
        $this->assertNull(Page::create([
            'slug' => 'test-2', 'title' => ['it' => 'T'], 'body' => ['it' => '<p>B</p>'],
        ])->last_updated_at);
    }
}
