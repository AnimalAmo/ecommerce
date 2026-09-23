<?php

namespace Tests\Feature\Admin\Search;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Search;
use App\Models\Order\Order;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SearchPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    public function test_a_guest_is_sent_to_the_panel_login(): void
    {
        $this->get(route('admin.search', ['q' => 'brescia']))->assertRedirect(route('admin.login'));
    }

    public function test_a_customer_is_refused(): void
    {
        Role::findOrCreate('client', 'web');
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('client');

        $this->actingAs($customer)->get(route('admin.search', ['q' => 'brescia']))->assertForbidden();
    }

    public function test_a_partner_is_refused(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('admin.search', ['q' => 'brescia']))->assertForbidden();
    }

    public function test_results_are_grouped_and_link_to_the_panel_screens(): void
    {
        $this->actingAsSuperadmin();
        $structure = Structure::factory()->create(['name' => ['it' => 'Hotel Brescia'], 'suspended_at' => now()]);
        $buyer = User::factory()->create(['first_name' => 'Giulia', 'last_name' => 'Brescianini', 'email' => 'giulia@example.com']);
        Order::factory()->for($buyer)->create([
            'order_number' => 'ORD-000042',
            'status' => OrderStatus::Paid,
            'total_cents' => 12000,
            'first_name' => 'Giulia',
            'last_name' => 'Brescianini',
        ]);

        $this->get(route('admin.search', ['q' => 'brescia']))
            ->assertOk()
            ->assertSee('Risultati per “brescia”')
            ->assertSee('2 risultati.')
            ->assertSeeInOrder(['Schede', 'Hotel Brescia', 'Sospesa', 'Iscritti', 'Giulia Brescianini', 'giulia@example.com'])
            ->assertSee(route('admin.catalog.show', ['type' => 'structure', 'id' => $structure->id]), false)
            ->assertSee(route('admin.users.show', ['user' => $buyer->id]), false)
            ->assertSee(route('admin.catalog.index', ['q' => 'brescia']), false)
            ->assertSee(route('admin.users.index', ['q' => 'brescia']), false)
            ->assertSee('Gli ordini si cercano per numero');

        $this->get(route('admin.search', ['q' => '42']))
            ->assertSeeInOrder(['Ordini', 'ORD-000042', 'Giulia Brescianini', Format::money(12000), 'Pagato']);
    }

    public function test_a_guest_checkout_order_has_no_account_to_open(): void
    {
        $this->actingAsSuperadmin();
        Order::factory()->guest()->create(['order_number' => 'ORD-000077', 'first_name' => 'Anna', 'last_name' => 'Neri']);

        $this->get(route('admin.search', ['q' => 'ORD-000077']))
            ->assertSee('Anna Neri')
            ->assertSee('senza account');
    }

    public function test_a_confirmed_on_site_order_gets_its_own_tone(): void
    {
        $this->actingAsSuperadmin();
        Order::factory()->guest()->onSite()->create(['order_number' => 'ORD-000043', 'first_name' => 'Anna', 'last_name' => 'Neri']);

        // Tono "info" (fondo brand-cyan-bg), non il "muted" del fallback.
        $this->get(route('admin.search', ['q' => 'ORD-000043']))
            ->assertOk()
            ->assertSeeInOrder(['ORD-000043', '!bg-brand-cyan-bg', __('orders.status.confirmed')], false);
    }

    public function test_a_short_term_asks_for_more(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.search', ['q' => 'a']))
            ->assertOk()
            ->assertSee('Cerca nel pannello')
            ->assertSee('Scrivi almeno 2 caratteri');
    }

    public function test_nothing_found_says_so(): void
    {
        $this->actingAsSuperadmin();

        Livewire::withQueryParams(['q' => 'zzzz'])
            ->test(Search::class)
            ->assertSet('q', 'zzzz')
            ->assertSee('Nessun risultato fra schede, iscritti e ordini.');
    }

    public function test_the_header_field_submits_here_and_keeps_the_term(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.search', ['q' => 'brescia']))
            ->assertSee('action="'.route('admin.search').'"', false)
            ->assertSee('name="q"', false)
            ->assertSee('value="brescia"', false);
    }
}
