<?php

namespace Tests\Feature;

use App\Livewire\Smartbox;
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
            ->assertSee('Il tuo weekend')
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
}
