<?php

namespace Tests\Feature\Partner;

use App\Livewire\Auth\AuthModal;
use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Iscrizione B2B, email già di un altro account.
 *
 * Il controllo resta allo step 2 (lo step 1 non ha `unique:users`: rivelerebbe
 * l'esistenza di un'account senza il throttle della modale di registrazione),
 * ma il conflitto non muore lì: si torna allo step 1 con l'errore sul campo
 * email e l'invito ad accedere. Dopo il login l'iscrizione riprende dallo
 * step 2 e l'account esistente viene promosso a partner.
 */
class PartnerEmailConflictTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function step1Data(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Susanna',
            'lastName' => 'Rossi',
            'businessName' => 'Hotel Rosovino',
            'email' => 'susanna@example.com',
            'address' => 'Via C. Pacini 19',
            'province' => 'MI',
            'zip' => '20131',
            'phone' => '3498798828',
            'vat' => '86334519757',
            'taxCode' => 'RSSSNN98A41F205X',
            'pec' => 'susanna@pec.it',
            'sdi' => 'SUBM70N',
        ], $overrides);
    }

    private function fillStep1($component, array $overrides = [])
    {
        foreach ($this->step1Data($overrides) as $field => $value) {
            $component->set("form.{$field}", $value);
        }

        return $component;
    }

    public function test_step_2_sends_a_taken_email_back_to_step_1(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'susanna@example.com']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'attivita')
            ->call('createAccount')
            ->assertRedirect(route('partner.register'));

        // Nessun account nuovo e nessun profilo partner: il conflitto è aperto.
        $this->assertSame(1, User::count());
        $this->assertSame('susanna@example.com', session('partner_registration.email_conflict'));

        // I dati dello step 1 e la tipologia scelta restano in sessione.
        $this->assertNotNull(session('partner_registration.step1'));
        $this->assertSame('attivita', session('partner_registration.service'));
    }

    public function test_step_2_restores_the_service_chosen_before_the_conflict(): void
    {
        session(['partner_registration.service' => 'attivita']);

        Livewire::test(PartnerRegisterStep2::class)
            ->assertSet('service', 'attivita');
    }

    public function test_step_1_shows_the_conflict_on_the_email_field_with_the_login_cta(): void
    {
        User::factory()->create(['email' => 'susanna@example.com']);
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
        ]);

        Livewire::test(PartnerRegisterStep1::class)
            ->assertSet('form.email', 'susanna@example.com')
            ->assertHasErrors('form.email')
            ->assertSee(__('partner.register.error_email_taken'))
            ->assertSee(__('partner.register.login_and_continue'));
    }

    public function test_step_1_blocks_the_flagged_email_again_instead_of_bouncing_to_step_2(): void
    {
        User::factory()->create(['email' => 'susanna@example.com']);
        session(['partner_registration.email_conflict' => 'susanna@example.com']);

        $component = $this->fillStep1(Livewire::test(PartnerRegisterStep1::class));

        $component->call('submit')
            ->assertHasErrors('form.email')
            ->assertSee(__('partner.register.error_email_taken'))
            ->assertNoRedirect();
    }

    public function test_step_1_lets_a_different_email_through_and_clears_the_conflict(): void
    {
        User::factory()->create(['email' => 'susanna@example.com']);
        session(['partner_registration.email_conflict' => 'susanna@example.com']);

        $component = $this->fillStep1(
            Livewire::test(PartnerRegisterStep1::class),
            ['email' => 'susanna.rossi@example.com'],
        );

        $component->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.register.step2'));

        $this->assertNull(session('partner_registration.email_conflict'));
    }

    public function test_the_login_cta_opens_the_login_modal_with_the_email_prefilled(): void
    {
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
        ]);

        Livewire::test(PartnerRegisterStep1::class)
            ->call('loginToContinue')
            ->assertDispatched('prefill-login-email', email: 'susanna@example.com')
            ->assertDispatched('modal-show', name: 'login');
    }

    public function test_the_login_modal_accepts_the_email_from_the_registration(): void
    {
        Livewire::test(AuthModal::class)
            ->dispatch('prefill-login-email', email: 'susanna@example.com')
            ->assertSet('form.email', 'susanna@example.com');
    }

    public function test_once_logged_in_step_1_resumes_from_step_2(): void
    {
        $user = User::factory()->create(['email' => 'susanna@example.com']);
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
            'partner_registration.service' => 'attivita',
        ]);

        Livewire::actingAs($user)
            ->test(PartnerRegisterStep1::class)
            ->assertRedirect(route('partner.register.step2'));

        // Conflitto risolto: l'email ora è quella dell'account.
        $this->assertNull(session('partner_registration.email_conflict'));
    }

    public function test_a_logged_in_user_without_the_saved_step_1_stays_on_step_1(): void
    {
        $user = User::factory()->create(['email' => 'susanna@example.com']);
        session(['partner_registration.email_conflict' => 'susanna@example.com']);

        Livewire::actingAs($user)
            ->test(PartnerRegisterStep1::class)
            ->assertNoRedirect()
            ->assertSet('form.email', 'susanna@example.com')
            ->assertHasNoErrors();
    }

    public function test_logging_in_with_another_account_promotes_that_account(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'susanna@example.com']);
        $other = User::factory()->create(['email' => 'susanna.pro@example.com']);
        $other->syncRoles(['client']);

        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.service' => 'attivita',
        ]);

        Livewire::actingAs($other)
            ->test(PartnerRegisterStep2::class)
            ->set('service', 'attivita')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $other->refresh();
        $this->assertTrue($other->hasRole('partner'));
        $this->assertTrue($other->hasRole('client'));
        $this->assertSame('Hotel Rosovino', $other->partnerProfile->business_name);

        // L'account omonimo del conflitto non è stato toccato.
        $this->assertNull(User::where('email', 'susanna@example.com')->firstOrFail()->partnerProfile);

        // La sessione di registrazione è consumata, servizio incluso.
        $this->assertNull(session('partner_registration.step1'));
        $this->assertNull(session('partner_registration.service'));
    }

    public function test_the_whole_conflict_flow_ends_on_the_promoted_account(): void
    {
        $this->seed(RoleSeeder::class);
        $client = User::factory()->create(['email' => 'susanna@example.com']);
        $client->syncRoles(['client']);

        // 1. Da sloggati: step 1 passa (nessun unique), step 2 rimbalza indietro.
        $this->fillStep1(Livewire::test(PartnerRegisterStep1::class))
            ->call('submit')
            ->assertRedirect(route('partner.register.step2'));

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertRedirect(route('partner.register'));

        // 2. Login dell'account in conflitto: lo step 1 riparte dallo step 2.
        Livewire::actingAs($client)
            ->test(PartnerRegisterStep1::class)
            ->assertRedirect(route('partner.register.step2'));

        // 3. Step 2 con la tipologia già scelta: l'account viene promosso.
        Livewire::actingAs($client)
            ->test(PartnerRegisterStep2::class)
            ->assertSet('service', 'struttura')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $client->refresh();
        $this->assertTrue($client->hasRole('partner'));
        $this->assertTrue($client->hasRole('client'));
        $this->assertSame('Hotel Rosovino', $client->partnerProfile->business_name);
        $this->assertSame(1, User::count());
    }
}
