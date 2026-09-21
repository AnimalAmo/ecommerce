<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use App\Services\Content\PageService;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Il PageSeeder crea solo le pagine che mancano: una revisione dei testi
 * legali fatta da noi (l'art. 8 delle Condizioni Fornitore, 14/09) arriva
 * su un database già seminato solo con una migration. Il pannello non è
 * mai stato in produzione, quindi una pagina con la data più vecchia di
 * quella del seeder è ancora il testo seminato, non uno della cliente.
 */
class RealignLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_09_21_100001_realign_legal_pages_with_seeded_texts.php';

    public function test_a_page_seeded_before_the_last_revision_gets_the_versioned_texts(): void
    {
        $this->seed(PageSeeder::class);

        // Com'era la pagina seminata l'08/09: art. 8 vecchio, data del 26/08.
        $page = Page::where('slug', Page::TERMS_SUPPLIERS)->sole();
        $page->setTranslation('title', 'it', 'Condizioni fornitore');
        $page->setTranslation('title', 'en', 'Supplier terms');
        $page->setTranslation('body', 'it', '<p>Art. 8: la provvigione si fattura a 30 giorni.</p>');
        $page->setTranslation('body', 'en', '<p>Art. 8: the commission is invoiced at 30 days.</p>');
        $page->last_updated_at = '2026-08-26';
        $page->save();

        $this->realign();

        $page->refresh();
        foreach (['it', 'en'] as $locale) {
            $this->assertSame(
                trim(file_get_contents(database_path("seeders/content/termini-e-condizioni-fornitori.{$locale}.html"))),
                $page->getTranslation('body', $locale, false),
                "corpo {$locale} ancora quello vecchio",
            );
            $this->assertSame(PageService::ORIGINAL, app(PageService::class)->localeState($page, $locale));
        }
        $this->assertSame('Condizioni generali di adesione fornitore', $page->getTranslation('title', 'it', false));
        $this->assertSame('Supplier general terms of adhesion', $page->getTranslation('title', 'en', false));
        $this->assertSame('2026-09-14', $page->last_updated_at->toDateString());
    }

    /**
     * Stessa data del seeder, data più recente o nessuna data: non è il testo
     * vecchio seminato, e riscriverlo cancellerebbe il lavoro di qualcuno.
     */
    public function test_a_page_at_the_revision_newer_or_undated_is_left_alone(): void
    {
        $this->seed(PageSeeder::class);

        $current = $this->edit(Page::TERMS_SUPPLIERS, '2026-09-14');
        $newer = $this->edit(Page::PRIVACY, '2026-10-01');
        $undated = $this->edit(Page::TERMS_CUSTOMERS, null);

        $this->realign();

        foreach ([$current, $newer, $undated] as $page) {
            $before = $page->last_updated_at?->toDateString();
            $page->refresh();

            $this->assertSame('<p>Scritto dal pannello.</p>', $page->getTranslation('body', 'it', false), "{$page->slug} riscritta");
            $this->assertSame('Titolo del pannello', $page->getTranslation('title', 'it', false));
            $this->assertSame($before, $page->last_updated_at?->toDateString());
        }
    }

    public function test_a_missing_page_is_not_created(): void
    {
        $this->realign();

        $this->assertSame(0, Page::count());
    }

    private function realign(): void
    {
        (require base_path(self::MIGRATION))->up();
    }

    private function edit(string $slug, ?string $lastUpdatedAt): Page
    {
        $page = Page::where('slug', $slug)->sole();
        $page->setTranslation('title', 'it', 'Titolo del pannello');
        $page->setTranslation('body', 'it', '<p>Scritto dal pannello.</p>');
        $page->last_updated_at = $lastUpdatedAt;
        $page->save();

        return $page;
    }
}
