<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageSeeder::class);
    }

    public function test_the_customer_terms_page_renders(): void
    {
        // assertSee con $escaped = false: il testo legale è pieno di apostrofi
        // tipografici, che nella pagina sono entità HTML.
        $this->get(route('terms.customers'))
            ->assertOk()
            ->assertSee('Termini e condizioni', false)
            ->assertSee('Definizioni', false);
    }

    public function test_the_supplier_terms_page_renders(): void
    {
        $this->get(route('terms.suppliers'))
            ->assertOk()
            ->assertSee('Premesse', false);
    }

    public function test_the_page_shows_the_last_update_date(): void
    {
        $this->get(route('terms.customers'))->assertSee('26/08/2026', false);
    }

    public function test_a_page_without_a_row_returns_404(): void
    {
        Page::where('slug', Page::TERMS_SUPPLIERS)->delete();

        $this->get(route('terms.suppliers'))->assertNotFound();
    }

    public function test_the_italian_route_has_no_locale_prefix_and_the_english_one_does(): void
    {
        // APP_LOCALE=it, quindi l'italiano è senza prefisso e l'inglese sotto /en.
        $this->assertStringEndsWith('/termini-e-condizioni', route('terms.customers'));

        $this->assertStringContainsString(
            '/en/terms-and-conditions',
            LaravelLocalization::getLocalizedURL('en', route('terms.customers')),
        );
    }
}
