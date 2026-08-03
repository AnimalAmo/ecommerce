<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Iscrizione B2B con un account disattivato.
 *
 * `RegisterPartnerAccount::promote()` di proposito non riattiva nessuno, e
 * l'area partner risponde 403 (EnsureActivePartner): senza un blocco a monte
 * l'utente completerebbe tutta l'iscrizione per sbattere su un 403 muto.
 * Si ferma prima, con l'invito a contattare l'assistenza.
 */
class PartnerInactiveAccountTest extends TestCase
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

    private function inactiveUser(): User
    {
        return User::factory()->inactive()->create(['email' => 'susanna@example.com']);
    }

    public function test_step_1_warns_a_disabled_account_before_the_form_is_filled(): void
    {
        Livewire::actingAs($this->inactiveUser())
            ->test(PartnerRegisterStep1::class)
            ->assertSet('accountInactive', true)
            ->assertSee(__('partner.register.error_account_inactive'))
            ->assertSee(__('partner.register.contact_support'));
    }

    public function test_step_1_refuses_to_advance_a_disabled_account(): void
    {
        $component = $this->fillStep1(Livewire::actingAs($this->inactiveUser())->test(PartnerRegisterStep1::class));

        $component->call('submit')
            ->assertNoRedirect()
            ->assertSet('accountInactive', true)
            ->assertSee(__('partner.register.error_account_inactive'));

        $this->assertNull(session('partner_registration.step1'));
    }

    public function test_step_2_refuses_to_promote_a_disabled_account(): void
    {
        $this->seed(RoleSeeder::class);
        $user = $this->inactiveUser();
        $user->syncRoles(['client']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::actingAs($user)
            ->test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertSet('accountInactive', true)
            ->assertSee(__('partner.register.error_account_inactive'))
            ->assertNoRedirect();

        $user->refresh();
        $this->assertFalse($user->hasRole('partner'));
        $this->assertNull($user->partnerProfile);

        // Dati E tipologia restano in sessione: riattivato l'account l'iscrizione
        // riprende senza far riscegliere il servizio.
        $this->assertNotNull(session('partner_registration.step1'));
        $this->assertSame('struttura', session('partner_registration.service'));
    }

    public function test_step_2_warns_a_disabled_account_on_load_not_on_click(): void
    {
        session(['partner_registration.step1' => $this->step1Data()]);

        $html = Livewire::actingAs($this->inactiveUser())
            ->test(PartnerRegisterStep2::class)
            ->assertSet('accountInactive', true)
            ->assertSee(__('partner.register.error_account_inactive'))
            ->assertSee(__('partner.register.contact_support'))
            ->html();

        $this->assertMatchesRegularExpression('/<button[^>]+disabled[^>]*>(?:(?!<\/button>).)*'.preg_quote(__('partner.register2.submit'), '/').'/s', $html);
    }

    public function test_the_step_1_cta_is_disabled_for_a_disabled_account(): void
    {
        $html = Livewire::actingAs($this->inactiveUser())
            ->test(PartnerRegisterStep1::class)
            ->html();

        $this->assertMatchesRegularExpression('/<button[^>]+disabled[^>]*>(?:(?!<\/button>).)*'.preg_quote(__('partner.register.next'), '/').'/s', $html);
    }

    public function test_a_disabled_account_is_never_forwarded_to_step_2(): void
    {
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
        ]);

        Livewire::actingAs($this->inactiveUser())
            ->test(PartnerRegisterStep1::class)
            ->assertNoRedirect()
            ->assertSet('accountInactive', true);
    }

    /**
     * A un anonimo lo stato dell'account non si dice: il messaggio è lo stesso
     * di un account attivo. Altrimenti lo step 1 diventa un oracolo `is_active`
     * interrogabile senza autenticazione né throttle.
     */
    public function test_a_guest_is_never_told_that_the_conflicting_account_is_disabled(): void
    {
        User::factory()->inactive()->create(['email' => 'susanna@example.com']);
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
        ]);

        Livewire::test(PartnerRegisterStep1::class)
            ->assertSet('emailConflict', true)
            ->assertSet('accountInactive', false)
            ->assertSee(__('partner.register.error_email_taken'))
            ->assertSee(__('partner.register.login_and_continue'))
            ->assertDontSee(__('partner.register.error_account_inactive'));
    }

    public function test_the_disabled_state_surfaces_only_after_the_login(): void
    {
        $user = $this->inactiveUser();
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
        ]);

        // Stesso stato di sessione del test precedente, ma autenticato: ora sì.
        Livewire::actingAs($user)
            ->test(PartnerRegisterStep1::class)
            ->assertSee(__('partner.register.error_account_inactive'))
            ->assertSee(__('partner.register.contact_support'))
            ->assertDontSee(__('partner.register.login_and_continue'));
    }

    public function test_a_guest_hitting_an_active_account_still_gets_the_login_cta(): void
    {
        User::factory()->create(['email' => 'susanna@example.com']);
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
        ]);

        Livewire::test(PartnerRegisterStep1::class)
            ->assertSee(__('partner.register.login_and_continue'))
            ->assertDontSee(__('partner.register.error_account_inactive'));
    }

    public function test_a_conflict_whose_account_disappeared_stops_blocking(): void
    {
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.email_conflict' => 'susanna@example.com',
        ]);

        Livewire::test(PartnerRegisterStep1::class)
            ->assertSet('emailConflict', false)
            ->assertHasNoErrors()
            ->assertDontSee(__('partner.register.login_and_continue'));

        $this->assertNull(session('partner_registration.email_conflict'));
    }

    public function test_an_active_account_is_still_promoted(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create(['email' => 'susanna@example.com']);
        $user->syncRoles(['client']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::actingAs($user)
            ->test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $this->assertTrue($user->refresh()->hasRole('partner'));
    }
}
