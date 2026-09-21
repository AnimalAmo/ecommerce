<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Content\PageEdit;
use App\Models\Page\Page;
use App\Services\Content\PageService;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class PageEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsSuperadmin();
        $this->seed(PageSeeder::class);
    }

    private function legal(string $slug = Page::PRIVACY): Page
    {
        return Page::where('slug', $slug)->sole();
    }

    public function test_the_editor_opens_a_legal_page_with_its_texts(): void
    {
        $page = $this->legal();

        $this->get(route('admin.pages.edit', $page))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Pagina legale')
            ->assertSee('/privacy-policy')
            ->assertSee('Data dell&#039;ultima revisione', false);

        Livewire::test(PageEdit::class, ['page' => $page])
            ->assertSet('title.it', 'Privacy Policy')
            ->assertSet('lastUpdatedAt', '2026-08-27')
            ->assertSet('body.en', $page->getTranslation('body', 'en'));
    }

    public function test_a_legal_page_is_rewritten_and_the_site_shows_the_new_text(): void
    {
        $page = $this->legal(Page::TERMS_CUSTOMERS);

        Livewire::test(PageEdit::class, ['page' => $page])
            ->set('title.it', 'Condizioni di vendita')
            ->set('body.it', '<h2>Premessa</h2><p>Il nuovo <strong>testo</strong>.</p>')
            ->set('lastUpdatedAt', '2026-09-21')
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame('Condizioni di vendita', $page->titleFor('it'));
        $this->assertSame('2026-09-21', $page->last_updated_at->toDateString());
        $this->assertSame(Page::KIND_LEGAL, $page->kind);
        $this->assertSame(Page::TERMS_CUSTOMERS, $page->slug);

        $this->get(route('terms.customers'))
            ->assertOk()
            ->assertSee('Condizioni di vendita')
            ->assertSee('<h2>Premessa</h2><p>Il nuovo <strong>testo</strong>.</p>', false)
            ->assertSee('21/09/2026');
    }

    public function test_the_body_is_sanitized_before_it_reaches_the_site(): void
    {
        $page = $this->legal();

        Livewire::test(PageEdit::class, ['page' => $page])
            ->set('body.it', '<p onclick="steal()">Ciao</p><script>alert(1)</script><a href="javascript:alert(1)">link</a><img src=x onerror=alert(1)>')
            ->call('save')
            ->assertHasNoErrors()
            // Il form riparte dall'HTML salvato: la cliente vede quello che vede il sito.
            ->assertSet('body.it', '<p>Ciao</p>link');

        $this->assertSame('<p>Ciao</p>link', $page->refresh()->getTranslation('body', 'it'));

        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('<p>Ciao</p>link', false)
            ->assertDontSee('steal()', false)
            ->assertDontSee('alert(1)', false);
    }

    public function test_an_empty_italian_body_is_refused(): void
    {
        $page = $this->legal();
        $original = $page->getTranslation('body', 'it');

        Livewire::test(PageEdit::class, ['page' => $page])
            ->set('locale', 'en')
            ->set('body.it', '<p></p>')
            ->call('save')
            ->assertHasErrors('body.it')
            ->assertSet('locale', 'it');

        $this->assertSame($original, $page->refresh()->getTranslation('body', 'it'));
    }

    public function test_a_missing_italian_title_moves_to_the_italian_tab(): void
    {
        Livewire::test(PageEdit::class, ['page' => $this->legal()])
            ->set('locale', 'en')
            ->set('title.it', '')
            ->call('save')
            ->assertHasErrors('title.it')
            ->assertSet('locale', 'it');
    }

    public function test_clearing_the_english_text_falls_back_to_italian(): void
    {
        $page = $this->legal();

        Livewire::test(PageEdit::class, ['page' => $page])
            ->set('body.en', '<p></p>')
            ->set('title.en', '')
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame('', (string) $page->getTranslation('body', 'en', false));
        $this->assertSame($page->bodyFor('it'), $page->bodyFor('en'));
    }

    public function test_a_free_page_is_created_published_and_linked_in_the_footer(): void
    {
        Livewire::test(PageEdit::class)
            ->set('title.it', 'Viaggiare in treno col cane')
            ->set('body.it', '<p>Le regole di Trenitalia.</p>')
            ->set('slug', '/Guide/Viaggiare in Treno')
            ->set('footerColumn', 'support')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.pages.edit', Page::where('slug', 'viaggiare-in-treno')->sole()));

        $page = Page::where('slug', 'viaggiare-in-treno')->sole();
        $this->assertSame(Page::KIND_FREE, $page->kind);
        $this->assertTrue($page->is_published);
        $this->assertSame('support', $page->footer_column);

        $this->get(route('page', ['slug' => 'viaggiare-in-treno']))
            ->assertOk()
            ->assertSee('Viaggiare in treno col cane')
            ->assertSee('<p>Le regole di Trenitalia.</p>', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('page', ['slug' => 'viaggiare-in-treno']), false);
    }

    public function test_a_free_page_needs_a_unique_address(): void
    {
        Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'guida',
            'title' => ['it' => 'Guida'],
            'body' => ['it' => '<p>Testo.</p>'],
        ]);

        Livewire::test(PageEdit::class)
            ->set('title.it', 'Altra guida')
            ->set('body.it', '<p>Testo.</p>')
            ->set('slug', 'Guida')
            ->call('save')
            ->assertHasErrors('slug');

        Livewire::test(PageEdit::class)
            ->set('title.it', 'Altra guida')
            ->set('body.it', '<p>Testo.</p>')
            ->set('slug', Page::PRIVACY)
            ->call('save')
            ->assertHasErrors('slug');

        $this->assertSame(1, Page::free()->count());
    }

    public function test_a_legal_page_keeps_its_address_and_stays_out_of_the_footer_columns(): void
    {
        $page = $this->legal();

        Livewire::test(PageEdit::class, ['page' => $page])
            ->set('slug', 'altro-indirizzo')
            ->set('footerColumn', 'company')
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame(Page::PRIVACY, $page->slug);
        $this->assertNull($page->footer_column);
    }

    public function test_the_footer_link_follows_the_page(): void
    {
        $page = Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'guida',
            'title' => ['it' => 'Guida al viaggio'],
            'body' => ['it' => '<p>Testo.</p>'],
            'footer_column' => 'company',
        ]);

        // Il piede è in cache: la prima lettura la riempie, le scritture devono svuotarla.
        $this->get(route('about'))->assertSee('Guida al viaggio');

        Livewire::test(PageEdit::class, ['page' => $page])
            ->set('title.it', 'Guida al viaggio in treno')
            ->set('footerColumn', '')
            ->call('save');

        $this->get(route('about'))->assertDontSee('Guida al viaggio');
    }

    public function test_a_free_page_can_be_deleted(): void
    {
        $page = Page::create([
            'kind' => Page::KIND_FREE,
            'slug' => 'guida',
            'title' => ['it' => 'Guida'],
            'body' => ['it' => '<p>Testo.</p>'],
            'footer_column' => 'company',
        ]);

        Livewire::test(PageEdit::class, ['page' => $page])
            ->assertSee('Eliminare questa pagina?')
            ->call('delete')
            ->assertRedirect(route('admin.pages.index'));

        $this->assertModelMissing($page);
        $this->get(route('page', ['slug' => 'guida']))->assertNotFound();
    }

    public function test_a_legal_page_cannot_be_deleted(): void
    {
        $page = $this->legal();

        Livewire::test(PageEdit::class, ['page' => $page])
            ->assertDontSee(__('admin-content.pages.delete'))
            ->call('delete');

        $this->assertModelExists($page);

        $this->expectException(InvalidArgumentException::class);
        app(PageService::class)->delete($page);
    }

    public function test_the_preview_shows_the_open_language_with_the_italian_fallback(): void
    {
        Livewire::test(PageEdit::class)
            ->set('title.it', 'Guida')
            ->set('body.it', '<p>Solo in italiano.</p><script>x</script>')
            ->call('preview')
            ->assertSee('Solo in italiano.')
            ->assertDontSee('<script>x</script>', false)
            ->set('locale', 'en')
            ->assertSee(__('admin-content.pages.preview_fallback'))
            ->assertSee('Solo in italiano.');
    }

    public function test_the_new_page_screen_renders(): void
    {
        $this->get(route('admin.pages.create'))
            ->assertOk()
            ->assertSee('Nuova pagina')
            ->assertSee('Pagina libera')
            ->assertSee('Visibile nel piede del sito');
    }
}
