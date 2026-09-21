<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Content\SitePageEdit;
use App\Models\Content\ContentBlock;
use App\Services\Content\ContentBlockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SitePageEditTest extends TestCase
{
    use RefreshDatabase;

    /** Indice di una chiave nel registro della sezione: i campi del componente sono posizionali. */
    private function indexOf(string $section, string $key): int
    {
        return array_search($key, array_keys(config("admin-content.sections.{$section}.blocks")), true);
    }

    public function test_every_registered_key_exists_in_both_language_files(): void
    {
        foreach (config('admin-content.sections') as $section => $definition) {
            $this->assertNotEmpty($definition['blocks'], $section);

            $this->assertNotSame("admin-content.sections.{$section}", __("admin-content.sections.{$section}"));

            foreach ($definition['blocks'] as $key => $type) {
                foreach (['it', 'en'] as $locale) {
                    $this->assertNotSame($key, trans($key, [], $locale), "{$key} manca in lang/{$locale}");
                }

                $this->assertContains($type, ['line', 'text', 'paragraphs']);
                $this->assertSame($type === 'paragraphs', is_array(trans($key, [], 'it')), $key);
                $this->assertIsString(__("admin-content.site_blocks.{$key}"), "etichetta di {$key}");
                $this->assertNotSame("admin-content.site_blocks.{$key}", __("admin-content.site_blocks.{$key}"));
            }
        }
    }

    public function test_cms_falls_back_to_the_language_file(): void
    {
        $this->assertSame(__('about.heading'), cms('about.heading'));
        $this->assertSame(__('about.body'), cms_paragraphs('about.body'));
        $this->assertSame(
            __('holiday.empty_catalogue_region_title', ['region' => 'Liguria']),
            cms('holiday.empty_catalogue_region_title', ['region' => 'Liguria']),
        );
    }

    public function test_an_override_replaces_the_text_only_in_its_language(): void
    {
        ContentBlock::create(['key' => 'about.heading', 'value' => ['it' => 'La nostra storia']]);

        $this->assertSame('La nostra storia', cms('about.heading'));

        app()->setLocale('en');
        $this->assertSame(trans('about.heading', [], 'en'), cms('about.heading'));
    }

    public function test_placeholders_are_replaced_in_the_override(): void
    {
        ContentBlock::create(['key' => 'holiday.empty_catalogue_region_title', 'value' => ['it' => 'Niente in :region, per ora']]);

        $this->assertSame('Niente in Liguria, per ora', cms('holiday.empty_catalogue_region_title', ['region' => 'Liguria']));
    }

    public function test_the_admin_rewrites_a_text_and_the_site_shows_it(): void
    {
        $this->actingAsSuperadmin();

        // La mappa in cache viene letta prima del salvataggio: il salvataggio deve invalidarla.
        $this->assertSame(__('about.heading'), cms('about.heading'));

        Livewire::test(SitePageEdit::class, ['section' => 'about'])
            ->set('values.it.'.$this->indexOf('about', 'about.heading'), 'La nostra storia')
            ->set('values.it.'.$this->indexOf('about', 'about.body'), "Primo paragrafo.\n\n\n\nSecondo paragrafo.")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('La nostra storia', cms('about.heading'));
        $this->assertSame(['Primo paragrafo.', 'Secondo paragrafo.'], cms_paragraphs('about.body'));
        $this->assertDatabaseCount('content_blocks', 2);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('La nostra storia')
            ->assertSee('<p>Secondo paragrafo.</p>', false)
            ->assertDontSee(__('about.body')[0]);
    }

    public function test_an_emptied_field_brings_back_the_original(): void
    {
        $admin = $this->actingAsSuperadmin();
        app(ContentBlockService::class)->save('about', [
            'it' => ['about.heading' => 'La nostra storia'],
            'en' => ['about.heading' => 'Our story'],
        ], $admin);

        Livewire::test(SitePageEdit::class, ['section' => 'about'])
            ->assertSet('values.it.'.$this->indexOf('about', 'about.heading'), 'La nostra storia')
            ->set('values.it.'.$this->indexOf('about', 'about.heading'), '   ')
            ->call('save');

        $block = ContentBlock::where('key', 'about.heading')->sole();
        $this->assertSame(['en' => 'Our story'], $block->getTranslations('value'));
        $this->assertSame($admin->id, $block->updated_by);
        $this->assertSame(__('about.heading'), cms('about.heading'));

        Livewire::test(SitePageEdit::class, ['section' => 'about'])
            ->set('values.en.'.$this->indexOf('about', 'about.heading'), '')
            ->call('save');

        $this->assertDatabaseCount('content_blocks', 0);
    }

    public function test_restore_removes_the_override_for_the_open_language(): void
    {
        $this->actingAsSuperadmin();
        app(ContentBlockService::class)->save('home', [
            'it' => ['home.hero_title' => 'Ciao'],
            'en' => ['home.hero_title' => 'Hello'],
        ]);

        $index = $this->indexOf('home', 'home.hero_title');

        Livewire::test(SitePageEdit::class, ['section' => 'home'])
            ->set('locale', 'en')
            ->call('restore', $index)
            ->assertSet("values.en.{$index}", '')
            ->assertSet("values.it.{$index}", 'Ciao');

        $this->assertSame(['it' => 'Ciao'], ContentBlock::where('key', 'home.hero_title')->sole()->getTranslations('value'));
    }

    public function test_a_text_that_drops_a_placeholder_is_rejected(): void
    {
        $this->actingAsSuperadmin();

        $index = $this->indexOf('holiday', 'holiday.empty_catalogue_region_title');

        Livewire::test(SitePageEdit::class, ['section' => 'holiday'])
            ->set('locale', 'it')
            ->set("values.en.{$index}", 'Nothing here yet')
            ->call('save')
            ->assertHasErrors("values.en.{$index}")
            // La scheda passa alla lingua dell'errore, o l'errore resterebbe invisibile.
            ->assertSet('locale', 'en');

        $this->assertDatabaseCount('content_blocks', 0);

        Livewire::test(SitePageEdit::class, ['section' => 'holiday'])
            ->set("values.en.{$index}", 'Nothing in :region yet')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('content_blocks', 1);
    }

    public function test_the_editor_shows_the_original_text_as_placeholder(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.pages.site', 'contact'))
            ->assertOk()
            ->assertSee('Contatti')
            ->assertSee(__('contact.intro'))
            ->assertSee('Pagina con una struttura fissa')
            ->assertSee('Questa pagina mostra ancora il testo scritto nel sito');
    }

    public function test_an_unknown_section_is_a_404(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.pages.site', 'nope'))->assertNotFound();
    }

    public function test_the_home_page_reads_its_texts_through_the_overrides(): void
    {
        ContentBlock::create(['key' => 'home.hero_title', 'value' => ['it' => 'Vacanze a quattro zampe']]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Vacanze a quattro zampe')
            ->assertDontSee(__('home.hero_title'));
    }
}
