<?php

namespace Tests\Feature\Admin\People;

use App\Livewire\Admin\People\UserShow;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Partner\PartnerApplication;
use App\Models\Partner\PartnerProfile;
use App\Models\Pet\Pet;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_profile_shows_orders_pets_and_applications(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create(['first_name' => 'Giulio', 'last_name' => 'Amadori', 'city' => 'Mantova']);
        $order = Order::factory()->paid()->for($user)->create(['total_cents' => 37000]);
        Pet::factory()->for($user)->create(['species' => 'Cane', 'name' => 'Birba']);
        PartnerApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Giulio',
            'last_name' => 'Amadori',
            'email' => $user->email,
            'phone' => '+393331112222',
            'city' => 'Mantova',
            'business_name' => 'Dog Sitter Amadori',
            'role' => 'Titolare',
            'offer_type' => 'Servizi per animali',
            'description' => 'Faccio il dog sitter.',
            'status' => PartnerApplication::STATUS_INVITED,
        ]);

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Giulio Amadori')
            ->assertSee('Mantova')
            ->assertSee($order->order_number)
            ->assertSee('€ 370')
            ->assertSee('Birba')
            ->assertSee('Dog Sitter Amadori')
            ->assertSee('Invitato');
    }

    public function test_each_order_lists_what_was_booked_and_when(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create();
        $order = Order::factory()->paid()->for($user)->create();
        OrderItem::factory()->for($order)->create([
            'title' => 'Hotel Brescia',
            'booked_from' => '2026-10-12 00:00:00',
            'booked_until' => '2026-10-14 00:00:00',
        ]);
        OrderItem::factory()->forSmartbox()->for($order)->create(['title' => 'Due notti sul Garda']);

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Prenotazioni')
            ->assertSee('Hotel Brescia')
            ->assertSee('12/10/2026 – 14/10/2026')
            ->assertSee('Due notti sul Garda');
    }

    public function test_a_partner_profile_shows_listings_and_bookings_received(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('partner', 'web');

        $partner = User::factory()->create();
        $partner->assignRole('partner');
        PartnerProfile::factory()->for($partner)->create(['business_name' => 'Hotel Brescia srl']);

        $live = Structure::factory()->for($partner)->create();
        $suspended = Structure::factory()->for($partner)->create();
        $suspended->forceFill(['suspended_at' => now()])->save();

        // Una prenotazione pagata e una no: conta solo la prima.
        $paid = Order::factory()->paid()->create();
        OrderItem::factory()->for($paid)->create(['purchasable_id' => $live->id, 'partner_user_id' => $partner->id]);
        OrderItem::factory()->for(Order::factory()->create())->create(['purchasable_id' => $live->id, 'partner_user_id' => $partner->id]);

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee('Hotel Brescia srl')
            ->assertSee('2 schede, 1 sospesa')
            ->assertSee('1 prenotazione pagata')
            ->assertSee(route('admin.catalog.index', ['partner' => $partner->id]), escape: false);
    }

    public function test_a_customer_profile_has_no_partner_box(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->create();

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertDontSee('Schede a catalogo');
    }

    public function test_the_account_can_be_deactivated_and_reactivated(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->create();

        $component = Livewire::test(UserShow::class, ['user' => $user])
            ->call('toggleActive');
        $this->assertFalse($user->fresh()->is_active);

        $component->call('toggleActive');
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_an_anonymised_account_cannot_be_reactivated(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->inactive()->create();
        $user->forceFill(['anonymized_at' => now()])->save();

        Livewire::test(UserShow::class, ['user' => $user])
            ->assertSee('Dati cancellati su richiesta')
            ->call('toggleActive');

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_anonymise_from_the_profile(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->create(['first_name' => 'Elena']);

        Livewire::test(UserShow::class, ['user' => $user])
            ->call('askAnonymize')
            ->assertSee('Gli ordini restano intatti')
            ->call('anonymize')
            ->assertSee('Dati cancellati su richiesta');

        $this->assertNotNull($user->fresh()->anonymized_at);
    }

    public function test_a_superadmin_has_no_profile_here(): void
    {
        $admin = $this->actingAsSuperadmin();
        Role::findOrCreate('superadmin', 'web');

        $this->get(route('admin.users.show', $admin))->assertNotFound();
    }
}
