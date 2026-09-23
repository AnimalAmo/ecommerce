<?php

namespace Tests\Feature\Admin;

use App\Models\Event\Event;
use App\Models\User;
use App\Services\Admin\Catalog\AdminServiceCreator;
use Database\Seeders\ProvinceSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Le schermate del pannello raggiungibili senza parametri. */
    public static function panelPages(): array
    {
        return [
            'home' => ['admin.home'],
            'cerca' => ['admin.search'],
            'catalogo' => ['admin.catalog.index'],
            'approvazioni' => ['admin.approvals'],
            'recensioni' => ['admin.reviews'],
            'pagine' => ['admin.pages.index'],
            'nuova pagina' => ['admin.pages.create'],
            'animal times' => ['admin.articles.index'],
            'nuovo articolo' => ['admin.articles.create'],
            'faq' => ['admin.faqs'],
            'community' => ['admin.community'],
            'iscritti' => ['admin.users.index'],
            'nuovo partner' => ['admin.users.create'],
            'contatti' => ['admin.inbox'],
            'newsletter' => ['admin.newsletter.index'],
            'nuova newsletter' => ['admin.newsletter.create'],
            'incassi' => ['admin.payouts'],
        ];
    }

    #[DataProvider('panelPages')]
    public function test_a_guest_is_sent_to_the_panel_login(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('admin.login'));
    }

    #[DataProvider('panelPages')]
    public function test_a_superadmin_can_open_every_panel_page(string $route): void
    {
        $this->actingAsSuperadmin();

        $this->get(route($route))->assertOk();
    }

    /**
     * Le schermate del pannello che hanno parametri nell'indirizzo: non
     * possono stare in panelPages(), che passa il solo nome a route().
     * L'elenco lo dà il service: una famiglia aggiunta lì è coperta da subito.
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function parameterisedPanelPages(): array
    {
        return collect(AdminServiceCreator::CREATABLE_FAMILIES)
            ->mapWithKeys(fn (string $family): array => [
                "nuova scheda {$family}" => ['admin.catalog.create', ['family' => $family]],
            ])
            ->all();
    }

    #[DataProvider('parameterisedPanelPages')]
    public function test_a_guest_is_sent_to_the_panel_login_from_a_page_with_parameters(string $route, array $parameters): void
    {
        $this->get(route($route, $parameters))->assertRedirect(route('admin.login'));
    }

    #[DataProvider('parameterisedPanelPages')]
    public function test_a_superadmin_can_open_every_panel_page_with_parameters(string $route, array $parameters): void
    {
        // Le pagine di creazione disegnano il select delle province: senza i
        // seeder la lista è vuota, non rotta, ma il test resta realistico.
        $this->seed([RegionSeeder::class, ProvinceSeeder::class]);
        $this->actingAsSuperadmin();

        $this->get(route($route, $parameters))->assertOk();
    }

    public function test_an_unknown_family_has_no_creation_page(): void
    {
        $this->actingAsSuperadmin();

        $this->get('/admin/catalog/new/pacchetti')->assertNotFound();
        $this->get('/admin/catalog/new/hotel')->assertNotFound();
    }

    public function test_a_customer_gets_a_403_not_the_login_form(): void
    {
        Role::findOrCreate('client', 'web');
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('client');

        $this->actingAs($customer)->get(route('admin.home'))->assertForbidden();
    }

    public function test_a_partner_is_refused(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('admin.home'))->assertForbidden();
    }

    public function test_a_deactivated_superadmin_is_refused(): void
    {
        $this->actingAsSuperadmin(['is_active' => false]);

        $this->get(route('admin.home'))->assertForbidden();
    }

    public function test_the_panel_is_not_indexed(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.home'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('admin.login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_the_panel_lives_outside_the_language_prefix(): void
    {
        $this->assertSame(url('/admin/dashboard'), route('admin.home'));
        $this->assertSame('admin', Route::getRoutes()->getByName('admin.home')->getPrefix());
    }

    public function test_the_bare_panel_address_leads_to_the_dashboard(): void
    {
        $this->actingAsSuperadmin();

        $this->get('/admin')->assertRedirect(route('admin.home'));
    }

    public function test_panel_addresses_are_in_english(): void
    {
        // Richiesta di Matteo del 21/09/2026: interfaccia in italiano, indirizzi in inglese.
        $this->assertSame(url('/admin/login'), route('admin.login'));
        $this->assertSame(url('/admin/reset-password/abc'), route('admin.password.reset', ['token' => 'abc']));
        $this->assertSame(url('/admin/catalog/structure/7'), route('admin.catalog.show', ['type' => 'structure', 'id' => 7]));
        $this->assertSame(url('/admin/catalog/new/structure'), route('admin.catalog.create', ['family' => 'structure']));
        $this->assertSame(url('/admin/users/export'), route('admin.users.export'));
        $this->assertSame(url('/admin/inbox'), route('admin.inbox'));
        $this->assertSame(url('/admin/payouts'), route('admin.payouts'));
    }

    public function test_list_filters_are_read_from_english_query_strings(): void
    {
        $this->actingAsSuperadmin();
        Event::factory()->create(['title' => ['it' => 'Puppy Yoga'], 'suspended_at' => now()]);
        Event::factory()->create(['title' => ['it' => 'Dog Trekking']]);

        $this->get('/admin/catalog?status=suspended&type=event')
            ->assertOk()
            ->assertSee('Puppy Yoga')
            ->assertDontSee('Dog Trekking');
    }

    public function test_the_layout_shows_the_navigation_of_the_design(): void
    {
        $this->actingAsSuperadmin(['first_name' => 'Silvia', 'last_name' => 'Rossi']);

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSeeInOrder(['Dashboard', 'Catalogo', 'Contenuti', 'Persone', 'Denaro'])
            ->assertDontSee('Panoramica')
            ->assertSee('Contatti e candidature')
            ->assertSee('Vai al sito pubblico')
            ->assertSee('SR');
    }

    public function test_logout_ends_the_session_and_returns_to_the_login(): void
    {
        $this->actingAsSuperadmin();

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_a_partner_cannot_open_the_new_listing_page(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('admin.catalog.create', ['family' => 'structure']))->assertForbidden();
    }
}
