<?php

namespace Tests\Feature;

use App\Livewire\Catalog\Smartbox;
use App\Models\SmartboxPackage\SmartboxPackage;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SmartboxPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_smartbox_grid_shows_the_twelve_boxes_with_type_chips(): void
    {
        $this->get('/smartbox')
            ->assertOk()
            ->assertSee('Weekend di relax in Lombardia')
            ->assertSee('Weekend nella capitale')
            ->assertSee('Soggiorno')
            ->assertSee('Benessere')
            ->assertSee('Avventura')
            ->assertSee('Gruppo (+5 persone)')
            ->assertSee("0,00\u{A0}€");
    }

    public function test_smartbox_detail_shows_price_validity_and_content_from_db(): void
    {
        $this->get('/smartbox/relax-lombardia')
            ->assertOk()
            ->assertSee('Weekend di relax in Lombardia')
            ->assertSee('Coppia - 2 persone')
            ->assertSee("215\u{A0}€")
            ->assertSee('1 anno')
            ->assertSee('Accesso alla Spa')
            ->assertSee('Dog sitter');
    }

    public function test_smartbox_detail_404_for_unknown_slug(): void
    {
        $this->get('/smartbox/cofanetto-inesistente')->assertNotFound();
    }

    public function test_smartbox_grid_paginates_beyond_twelve_boxes(): void
    {
        // I 12 box del seeder stanno in una pagina (paginazione nascosta);
        // oltre la dozzina la seconda pagina è reale e navigabile.
        $extra = SmartboxPackage::factory()->create([
            'title' => 'Cofanetto tredicesimo',
            'slug' => 'tredicesimo',
            'position' => 99,
        ]);

        Livewire::test(Smartbox::class)
            ->assertSee('Weekend di relax in Lombardia')
            ->assertDontSee('Cofanetto tredicesimo')
            ->call('gotoPage', 2)
            ->assertSee('Cofanetto tredicesimo')
            ->assertDontSee('Weekend di relax in Lombardia')
            ->call('previousPage')
            ->assertSee('Weekend di relax in Lombardia')
            ->assertDontSee('Cofanetto tredicesimo');

        $this->assertSame(99, $extra->position);
    }

    public function test_smartbox_pagination_hidden_with_a_single_page(): void
    {
        // Col solo catalogo XD (12 box) non c'è una seconda pagina: nessun controllo.
        Livewire::test(Smartbox::class)
            ->assertDontSeeHtml('aria-label="Paginazione"');
    }

    public function test_smartbox_chips_restrict_the_grid_by_type(): void
    {
        Livewire::test(Smartbox::class)
            ->call('toggleSmartboxType', 'benessere')
            ->assertSee('Weekend di relax in Lombardia')
            ->assertDontSee('Weekend nella capitale');
    }

    public function test_smartbox_desktop_type_pill_restricts_the_grid_via_wire_model(): void
    {
        // Le pill dropdown desktop scrivono smartboxTypes via wire:model (non i toggle del modal).
        Livewire::test(Smartbox::class)
            ->set('smartboxTypes', ['benessere'])
            ->assertSee('Weekend di relax in Lombardia')
            ->assertDontSee('Weekend nella capitale');
    }

    public function test_smartbox_desktop_type_pill_resets_pagination(): void
    {
        SmartboxPackage::factory()->create([
            'title' => 'Cofanetto tredicesimo',
            'slug' => 'tredicesimo',
            'position' => 99,
        ]);

        Livewire::test(Smartbox::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('smartboxTypes', ['benessere'])
            ->assertSet('paginators.page', 1);
    }

    public function test_smartbox_search_filters_by_title(): void
    {
        Livewire::test(Smartbox::class)
            ->set('where', 'capitale')
            ->call('search')
            ->assertSee('Weekend nella capitale')
            ->assertDontSee('Weekend di relax in Lombardia');
    }

    public function test_smartbox_empty_result_falls_back_to_similar_results(): void
    {
        // I cofanetti seedati hanno price_from_cents 0: qualunque fascia diversa dai
        // default li esclude. L'XD app non lascia la griglia vuota ma propone card simili.
        Livewire::test(Smartbox::class)
            ->set('priceMin', 100)
            ->assertSee('Nessun risultato trovato')
            ->assertSee('Risultati simili alla tua ricerca:')
            ->assertSee('Weekend di relax in Lombardia');
    }

    public function test_smartbox_load_more_extends_the_first_page(): void
    {
        SmartboxPackage::factory()->create([
            'title' => 'Cofanetto tredicesimo',
            'slug' => 'tredicesimo',
            'position' => 99,
        ]);

        Livewire::test(Smartbox::class)
            ->assertDontSee('Cofanetto tredicesimo')
            ->call('loadMore')
            ->assertSee('Weekend di relax in Lombardia')
            ->assertSee('Cofanetto tredicesimo');
    }
}
