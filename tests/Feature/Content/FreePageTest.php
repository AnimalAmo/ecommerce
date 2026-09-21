<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pagine libere create dal pannello, servite da /pagina/{slug}. */
class FreePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_free_page_renders_its_title_and_body(): void
    {
        Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'guida-treno',
            'title' => ['it' => 'Viaggiare in treno col cane'],
            'body' => ['it' => '<h2>Le regole</h2><p>Museruola e guinzaglio.</p>'],
        ]);

        $this->get('/pagina/guida-treno')
            ->assertOk()
            ->assertSee('<title>Viaggiare in treno col cane', false)
            ->assertSee('<h2>Le regole</h2><p>Museruola e guinzaglio.</p>', false);
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->get('/pagina/non-esiste')->assertNotFound();
    }

    /** Le legali hanno la loro rotta: servirle anche qui creerebbe un duplicato. */
    public function test_a_legal_page_is_not_served_as_a_free_page(): void
    {
        $this->seed(PageSeeder::class);

        $this->get('/pagina/'.Page::PRIVACY)->assertNotFound();
    }

    public function test_an_unpublished_free_page_is_a_404(): void
    {
        Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'bozza',
            'title' => ['it' => 'Bozza'],
            'body' => ['it' => '<p>Testo.</p>'],
            'is_published' => false,
        ]);

        $this->get('/pagina/bozza')->assertNotFound();
    }

    public function test_the_footer_lists_the_free_pages_in_their_column(): void
    {
        Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'garanzie',
            'title' => ['it' => 'Le nostre garanzie', 'en' => 'Our guarantees'],
            'body' => ['it' => '<p>Testo.</p>'],
            'footer_column' => 'support',
        ]);
        Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'nascosta',
            'title' => ['it' => 'Fuori dal piede'],
            'body' => ['it' => '<p>Testo.</p>'],
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Le nostre garanzie')
            ->assertSee('/pagina/garanzie', false)
            ->assertDontSee('Fuori dal piede');
    }

    public function test_the_sitemap_lists_the_published_free_pages(): void
    {
        Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'garanzie',
            'title' => ['it' => 'Le nostre garanzie'],
            'body' => ['it' => '<p>Testo.</p>'],
        ]);
        Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'bozza',
            'title' => ['it' => 'Bozza'],
            'body' => ['it' => '<p>Testo.</p>'],
            'is_published' => false,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/pagina/garanzie', false)
            ->assertSee('/en/page/garanzie', false)
            ->assertDontSee('/pagina/bozza', false);
    }
}
