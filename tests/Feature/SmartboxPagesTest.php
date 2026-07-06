<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
