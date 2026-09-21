<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Da quando la cliente scrive dal pannello, il database è la fonte di verità
 * dei contenuti: un db:seed (lo lancia anche il deploy) non deve riportare i
 * testi a quelli versionati.
 */
class ContentSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeding_keeps_the_legal_pages_edited_from_the_panel(): void
    {
        $this->seed(PageSeeder::class);

        $page = Page::where('slug', Page::PRIVACY)->sole();
        $page->setTranslation('title', 'it', 'Informativa della cliente');
        $page->setTranslation('body', 'it', '<p>Scritto dal pannello.</p>');
        $page->last_updated_at = '2026-10-01';
        $page->save();

        $this->seed(PageSeeder::class);

        $page->refresh();
        $this->assertSame('Informativa della cliente', $page->titleFor('it'));
        $this->assertSame('<p>Scritto dal pannello.</p>', $page->bodyFor('it'));
        $this->assertSame('2026-10-01', $page->last_updated_at->toDateString());
        $this->assertSame(3, Page::count());
    }

    public function test_reseeding_brings_back_a_legal_page_that_is_missing(): void
    {
        $this->seed(PageSeeder::class);
        Page::where('slug', Page::TERMS_SUPPLIERS)->delete();

        $this->seed(PageSeeder::class);

        $this->assertSame(Page::KIND_LEGAL, Page::where('slug', Page::TERMS_SUPPLIERS)->sole()->kind);
    }
}
