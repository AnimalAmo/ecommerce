<?php

namespace Tests\Feature\Admin\Dashboard;

use App\Models\ContactMessage\ContactMessage;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\Partner\PartnerApplication;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->travelTo(CarbonImmutable::parse('2026-09-21 08:00:00', 'UTC'));
    }

    public function test_a_guest_is_sent_to_the_panel_login(): void
    {
        $this->get(route('admin.home'))->assertRedirect(route('admin.login'));
    }

    public function test_a_customer_is_refused(): void
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

    public function test_the_home_greets_the_admin_and_shows_what_is_waiting(): void
    {
        $this->actingAsSuperadmin(['first_name' => 'Silvia']);
        config(['admin.moderation' => true]);

        Structure::factory()->create([
            'name' => ['it' => 'Lamasu W&R'],
            'approval_status' => 'pending',
            'approval_requested_at' => now()->subDays(2),
            'created_at' => now()->subHours(2),
        ]);
        Event::factory()->create(['title' => ['it' => 'Puppy Yoga'], 'suspended_at' => now(), 'created_at' => now()->subDay()]);
        Order::factory()->guest()->paid()->create(['total_cents' => 48310, 'created_at' => now()->subDay()]);
        ContactMessage::query()->forceCreate([
            'first_name' => 'Luca', 'last_name' => 'Ferrari', 'email' => 'luca@example.com',
            'reason' => 'Informazioni generali', 'message' => 'Il cane può stare in camera al Lamasu?',
        ]);
        PartnerApplication::query()->forceCreate([
            'first_name' => 'Marta', 'last_name' => 'Belloni', 'email' => 'marta@example.com', 'phone' => '+393331234567',
            'city' => 'Brescia', 'business_name' => 'Dog Academy', 'role' => 'Titolare', 'offer_type' => 'Attività', 'description' => 'Addestratrice.',
        ]);

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSee('Buongiorno Silvia')
            ->assertSee('Lunedì 21 settembre — ecco cosa è arrivato dal sito.')
            ->assertSee('scheda da approvare')
            ->assertSee('la più vecchia da 2 giorni')
            ->assertSee('candidatura')
            ->assertSee('messaggio')
            ->assertSeeInOrder(['Schede pubblicate', 'Iscritti', 'Ordini del mese', 'Partner attivi'])
            ->assertSee('1 sospesa')
            ->assertSee(Format::money(48310).' incassati')
            ->assertSeeInOrder(['Ultime schede pubblicate', 'Lamasu W&amp;R', 'In attesa', 'Puppy Yoga', 'Sospesa'], false)
            ->assertSee(route('admin.catalog.show', ['type' => 'structure', 'id' => Structure::withHidden()->value('id')]), false)
            ->assertSeeInOrder(['Arrivato dal sito', 'Candidatura', 'Marta Belloni', 'Dog Academy — Attività, Brescia'])
            ->assertSee('Il cane può stare in camera al Lamasu?');
    }

    public function test_an_empty_site_says_so_instead_of_showing_blank_boxes(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSee('Nessuna scheda a catalogo.')
            ->assertSee('Niente di nuovo dal sito.')
            ->assertDontSee('schede da approvare');
    }
}
