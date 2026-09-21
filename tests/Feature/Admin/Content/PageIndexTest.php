<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Content\PageIndex;
use App\Models\Content\ContentBlock;
use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsSuperadmin();
        $this->seed(PageSeeder::class);
    }

    private function freePage(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'kind' => Page::KIND_FREE,
            'slug' => 'viaggiare-in-treno',
            'title' => ['it' => 'Viaggiare in treno col cane'],
            'body' => ['it' => '<p>Testo.</p>'],
        ], $attributes));
    }

    public function test_the_list_has_the_site_sections_the_legal_pages_and_the_free_pages(): void
    {
        $this->freePage();

        $this->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('Chi siamo')
            ->assertSee('Contatti')
            ->assertSee('Termini e condizioni')
            ->assertSee('Privacy Policy')
            ->assertSee('Viaggiare in treno col cane')
            ->assertSee('/pagina/viaggiare-in-treno')
            ->assertSee('Dove non scrivi niente resta il testo attuale');
    }

    public function test_untouched_pages_are_marked_as_original_and_never_modified(): void
    {
        $rows = collect(Livewire::test(PageIndex::class)->viewData('rows'))->keyBy('name');

        $this->assertSame(['it' => 'original', 'en' => 'original'], $rows['Chi siamo']['locales']);
        $this->assertSame(['it' => 'original', 'en' => 'original'], $rows['Termini e condizioni']['locales']);
        $this->assertNull($rows['Termini e condizioni']['updated_at']);
        $this->assertNull($rows['Chi siamo']['updated_at']);
    }

    public function test_a_rewritten_text_and_a_missing_translation_show_up(): void
    {
        ContentBlock::create(['key' => 'about.heading', 'value' => ['it' => 'La nostra storia']]);
        $this->freePage();

        $privacy = Page::where('slug', Page::PRIVACY)->sole();
        $privacy->setTranslation('body', 'it', '<p>Nuova informativa.</p>')->save();

        $rows = collect(Livewire::test(PageIndex::class)->viewData('rows'))->keyBy('name');

        $this->assertSame(['it' => 'rewritten', 'en' => 'original'], $rows['Chi siamo']['locales']);
        $this->assertSame(['it' => 'rewritten', 'en' => 'original'], $rows['Privacy Policy']['locales']);
        $this->assertNotNull($rows['Privacy Policy']['updated_at']);
        $this->assertSame(['it' => 'rewritten', 'en' => 'missing'], $rows['Viaggiare in treno col cane']['locales']);
    }

    public function test_every_row_has_one_state_shown_in_its_own_column(): void
    {
        ContentBlock::create(['key' => 'about.heading', 'value' => ['it' => 'La nostra storia']]);
        $this->freePage();

        $rows = collect(Livewire::test(PageIndex::class)->viewData('rows'))->keyBy('name');

        $this->assertSame('original', $rows['Termini e condizioni']['state']);
        $this->assertSame('rewritten', $rows['Chi siamo']['state']);
        // Riscritta in italiano ma senza inglese: conta la traduzione che manca.
        $this->assertSame('missing', $rows['Viaggiare in treno col cane']['state']);

        $this->get(route('admin.pages.index'))
            ->assertSeeInOrder(['Tipo', 'Stato', 'Italiano', 'Inglese']);
    }

    public function test_filters_narrow_the_list(): void
    {
        $this->freePage();

        Livewire::test(PageIndex::class)
            ->set('kind', 'legal')
            ->assertSee('Termini e condizioni')
            ->assertDontSee('Chi siamo')
            ->assertDontSee('Viaggiare in treno col cane')
            ->set('kind', 'free')
            ->assertSee('Viaggiare in treno col cane')
            ->assertDontSee('Termini e condizioni')
            ->set('kind', '')
            ->set('state', 'missing')
            ->assertSee('Viaggiare in treno col cane')
            ->assertDontSee('Chi siamo')
            ->set('state', '')
            ->set('search', 'privacy')
            ->assertSee('Privacy Policy')
            ->assertDontSee('Termini e condizioni')
            ->set('search', 'zzz')
            ->assertSee(__('admin-content.common.no_results'));
    }

    public function test_every_row_links_to_its_editor(): void
    {
        $page = $this->freePage();

        $this->get(route('admin.pages.index'))
            ->assertSee(route('admin.pages.site', 'about'), false)
            ->assertSee(route('admin.pages.edit', $page), false)
            ->assertSee(route('admin.pages.create'), false);
    }
}
