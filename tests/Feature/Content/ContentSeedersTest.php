<?php

namespace Tests\Feature\Content;

use App\Models\Article\Article;
use App\Models\Page\Page;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Il seed di piattaforma gira anche in produzione (ruoli, regioni): un
     * articolo tolto dalla cliente non deve tornare online.
     */
    public function test_reseeding_does_not_bring_back_an_article_deleted_from_the_panel(): void
    {
        Storage::fake('public');
        $this->seed(ArticleSeeder::class);
        Article::where('slug', 'viaggiare-con-il-tuo-animale')->sole()->delete();

        $this->seed(ArticleSeeder::class);

        $this->assertFalse(Article::where('slug', 'viaggiare-con-il-tuo-animale')->exists(), 'articolo cancellato ripubblicato');
        $this->assertSame(3, Article::count());
    }

    /** Lo slug cambiato dal pannello non fa rinascere l'originale accanto. */
    public function test_reseeding_does_not_duplicate_an_article_whose_slug_was_changed(): void
    {
        Storage::fake('public');
        $this->seed(ArticleSeeder::class);
        Article::where('slug', 'come-gestire-i-bisogni-del-cucciolo')->sole()->update(['slug' => 'bisogni-cucciolo']);

        $this->seed(ArticleSeeder::class);

        $this->assertFalse(Article::where('slug', 'come-gestire-i-bisogni-del-cucciolo')->exists(), 'doppione pubblicato');
        $this->assertSame(4, Article::count());
    }
}
