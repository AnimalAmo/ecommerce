<?php

namespace Tests\Feature\Admin\Content;

use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Le schermate del modulo Contenuti: solo il superadmin entra. */
class ContentAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function screens(): array
    {
        return [
            'pagine' => ['admin.pages.index', []],
            'nuova pagina' => ['admin.pages.create', []],
            'pagina legale' => ['admin.pages.edit', ['page' => 1]],
            'testi del sito' => ['admin.pages.site', ['section' => 'home']],
            'animal times' => ['admin.articles.index', []],
            'nuovo articolo' => ['admin.articles.create', []],
            'faq' => ['admin.faqs', []],
            'community' => ['admin.community', []],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageSeeder::class);
    }

    #[DataProvider('screens')]
    public function test_a_guest_goes_to_the_panel_login(string $route, array $parameters): void
    {
        $this->get(route($route, $parameters))->assertRedirect(route('admin.login'));
    }

    #[DataProvider('screens')]
    public function test_a_customer_is_refused(string $route, array $parameters): void
    {
        Role::findOrCreate('client', 'web');
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('client');

        $this->actingAs($customer)->get(route($route, $parameters))->assertForbidden();
    }

    #[DataProvider('screens')]
    public function test_a_partner_is_refused(string $route, array $parameters): void
    {
        $this->actingAsActivePartner();

        $this->get(route($route, $parameters))->assertForbidden();
    }

    #[DataProvider('screens')]
    public function test_the_superadmin_gets_in(string $route, array $parameters): void
    {
        $this->actingAsSuperadmin();

        $this->get(route($route, $parameters))->assertOk();
    }
}
