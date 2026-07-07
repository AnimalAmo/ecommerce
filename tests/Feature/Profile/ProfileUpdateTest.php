<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\Profile;
use App\Models\Pet\Pet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** Payload valido per tutti i campi obbligatori del form. */
    private const VALID = [
        'firstName' => 'Maria',
        'lastName' => 'Bianchi',
        'birthDate' => '01/12/1990',
        'email' => 'maria.bianchi@example.com',
        'petType' => 'Gatto',
        'address' => 'Via Roma 1',
        'city' => 'Torino',
        'zip' => '10121',
        'phone' => '333 1234567',
    ];

    public function test_mount_shows_seeded_demo_values(): void
    {
        $this->seed(DatabaseSeeder::class);
        $giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();

        Livewire::actingAs($giulia)
            ->test(Profile::class)
            ->assertSet('firstName', 'Giulia')
            ->assertSet('lastName', 'Rossi')
            ->assertSet('birthDate', '22/03/1998')
            ->assertSet('email', 'giulia.rossi@gmail.com')
            ->assertSet('petType', 'Cane')
            ->assertSet('address', 'Viale Abruzzi 20')
            ->assertSet('city', 'Milano')
            ->assertSet('zip', '20131')
            ->assertSet('phone', '340 5738920');
    }

    public function test_save_persists_all_columns_and_pet_species(): void
    {
        $user = User::factory()->create();
        Pet::factory()->for($user)->create(['species' => 'Cane']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set(self::VALID)
            // L'email cambia rispetto al valore del factory: serve la password attuale.
            ->set('currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show');

        $user->refresh();
        $this->assertSame('Maria', $user->first_name);
        $this->assertSame('Bianchi', $user->last_name);
        $this->assertSame('1990-12-01', $user->birth_date->toDateString());
        $this->assertSame('maria.bianchi@example.com', $user->email);
        $this->assertSame('Via Roma 1', $user->address);
        $this->assertSame('Torino', $user->city);
        $this->assertSame('10121', $user->postal_code);
        $this->assertSame('333 1234567', $user->phone);

        // Il pet esistente viene aggiornato, non duplicato.
        $this->assertSame('Gatto', $user->pets()->sole()->species);
    }

    public function test_save_creates_pet_when_user_has_none(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'petType' => 'Coniglio'])
            ->set('currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Coniglio', $user->pets()->sole()->species);
    }

    public function test_invalid_birth_date_is_rejected(): void
    {
        $user = User::factory()->create();

        // Formato errato (ISO invece di gg/mm/aaaa).
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'birthDate' => '1990-12-01'])
            ->call('save')
            ->assertHasErrors(['birthDate' => 'date_format']);

        // Data futura.
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'birthDate' => '01/01/2999'])
            ->call('save')
            ->assertHasErrors(['birthDate' => 'before']);
    }

    public function test_email_unique_ignores_self(): void
    {
        $user = User::factory()->create(['email' => 'propria@example.com']);
        User::factory()->create(['email' => 'altrui@example.com']);

        // Salvare mantenendo la propria email non genera errori.
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'email' => 'propria@example.com'])
            ->call('save')
            ->assertHasNoErrors();

        // L'email di un altro utente viene rifiutata.
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'email' => 'altrui@example.com'])
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    }

    /**
     * Regressione security: cambiare l'email (vettore di takeover permanente via
     * recupero password dirottato) richiede la password attuale.
     */
    public function test_email_change_requires_current_password(): void
    {
        $user = User::factory()->create(['email' => 'vittima@example.com', 'password' => 'password']);

        // Nuova email senza password attuale: bloccato, email invariata.
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'email' => 'attaccante@example.com'])
            ->call('save')
            ->assertHasErrors(['currentPassword' => 'required']);

        $this->assertSame('vittima@example.com', $user->fresh()->email);

        // Password attuale errata: ancora bloccato.
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'email' => 'attaccante@example.com'])
            ->set('currentPassword', 'sbagliata')
            ->call('save')
            ->assertHasErrors(['currentPassword' => 'current_password']);

        $this->assertSame('vittima@example.com', $user->fresh()->email);

        // Password attuale corretta: il cambio email va a buon fine.
        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set([...self::VALID, 'email' => 'attaccante@example.com'])
            ->set('currentPassword', 'password')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('attaccante@example.com', $user->fresh()->email);
    }
}
