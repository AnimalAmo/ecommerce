<?php

namespace Tests\Feature\Admin\People;

use App\Livewire\Admin\People\UserIndex;
use App\Models\Order\Order;
use App\Models\Pet\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(User $user, string $status, bool $byEmailOnly = false): void
    {
        DB::table('newsletter_subscribers')->insert([
            'email' => mb_strtolower($user->email),
            'user_id' => $byEmailOnly ? null : $user->id,
            'status' => $status,
            'source' => 'footer',
            'token' => Str::random(64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_list_shows_real_users_with_orders_spent_and_newsletter(): void
    {
        $admin = $this->actingAsSuperadmin(['first_name' => 'Ada', 'last_name' => 'Admin']);

        $luca = User::factory()->create(['first_name' => 'Luca', 'last_name' => 'Ferrari', 'email' => 'luca@example.com']);
        Order::factory()->paid()->for($luca)->create(['total_cents' => 40000]);
        Order::factory()->paid()->for($luca)->create(['total_cents' => 84000]);
        // Né i pending né gli annullati sono soldi incassati.
        Order::factory()->for($luca)->create(['total_cents' => 99900]);
        $this->subscribe($luca, 'confirmed');

        $sara = User::factory()->create(['first_name' => 'Sara', 'last_name' => 'Monti']);
        $this->subscribe($sara, 'pending', byEmailOnly: true);

        Livewire::test(UserIndex::class)
            ->assertSee('Luca Ferrari')
            ->assertSee('luca@example.com')
            ->assertSee('€ 1.240')
            ->assertSee('Sara Monti')
            ->assertSee('In attesa')
            ->assertDontSee($admin->email)
            ->assertSee('2 utenti registrati. 2 hanno chiesto la newsletter.');
    }

    public function test_filters_narrow_the_list(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('partner', 'web');

        $withNl = User::factory()->create(['first_name' => 'Nora', 'last_name' => 'Lista']);
        $this->subscribe($withNl, 'confirmed');
        $without = User::factory()->create(['first_name' => 'Ugo', 'last_name' => 'Senza']);
        $inactive = User::factory()->inactive()->create(['first_name' => 'Ivo', 'last_name' => 'Spento']);
        $old = User::factory()->create(['first_name' => 'Olga', 'last_name' => 'Vecchia', 'created_at' => now()->subYears(2)]);
        $partner = User::factory()->create(['first_name' => 'Paola', 'last_name' => 'Partner']);
        $partner->assignRole('partner');

        Livewire::test(UserIndex::class)
            ->set('newsletter', 'with')
            ->assertSee('Nora Lista')->assertDontSee('Ugo Senza')
            ->set('newsletter', 'without')
            ->assertSee('Ugo Senza')->assertDontSee('Nora Lista')
            ->set('newsletter', 'all')
            ->set('status', 'inactive')
            ->assertSee('Ivo Spento')->assertDontSee('Ugo Senza')
            ->set('status', 'all')
            ->set('period', '30d')
            ->assertSee('Ugo Senza')->assertDontSee('Olga Vecchia')
            ->set('period', 'always')
            ->set('role', 'partner')
            ->assertSee('Paola Partner')->assertDontSee('Ugo Senza')
            ->set('role', 'all')
            ->set('q', 'olga vecc')
            ->assertSee('Olga Vecchia')->assertDontSee('Ugo Senza');
    }

    public function test_columns_sort_both_ways(): void
    {
        $this->actingAsSuperadmin();

        $big = User::factory()->create(['first_name' => 'Grande', 'last_name' => 'Spesa']);
        Order::factory()->paid()->for($big)->create(['total_cents' => 90000]);
        $small = User::factory()->create(['first_name' => 'Piccola', 'last_name' => 'Spesa']);
        Order::factory()->paid()->for($small)->create(['total_cents' => 1000]);

        Livewire::test(UserIndex::class)
            ->call('sortBy', 'spent')
            ->assertSet('sort', 'spent')->assertSet('dir', 'desc')
            ->assertSeeInOrder(['Grande Spesa', 'Piccola Spesa'])
            ->call('sortBy', 'spent')
            ->assertSet('dir', 'asc')
            ->assertSeeInOrder(['Piccola Spesa', 'Grande Spesa']);
    }

    public function test_cancel_on_request_anonymises_the_user_and_keeps_orders(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create(['first_name' => 'Sara', 'last_name' => 'Monti', 'email' => 's.monti@example.com', 'phone' => '+393331234567']);
        $order = Order::factory()->paid()->for($user)->create();
        Pet::factory()->for($user)->create();
        $this->subscribe($user, 'confirmed');

        Livewire::test(UserIndex::class)
            ->call('askAnonymize', $user->id)
            ->assertSee('Cancellare questo contatto?')
            ->assertSee('s.monti@example.com')
            ->call('anonymize')
            ->assertSet('anonymizingId', null);

        $user->refresh();
        $this->assertSame('Utente anonimizzato', $user->name);
        $this->assertSame("anonimo-{$user->id}@anonimizzato.invalid", $user->email);
        $this->assertNull($user->phone);
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->anonymized_at);
        $this->assertSame(0, $user->pets()->count());
        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 's.monti@example.com']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
    }

    public function test_panel_page_renders(): void
    {
        $this->actingAsSuperadmin();
        User::factory()->create(['first_name' => 'Visibile']);

        $this->get(route('admin.users.index'))->assertOk()->assertSee('Visibile')->assertSee('Esporta in Excel');
    }
}
