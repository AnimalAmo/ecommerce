<?php

namespace Tests\Feature\Admin\People;

use App\Exceptions\PartnerAccountException;
use App\Exceptions\PaymentModeException;
use App\Livewire\Auth\ResetPassword;
use App\Mail\PartnerWelcomeMail;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Admin\People\PartnerAccountService;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mockery\MockInterface;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Mail::fake();

        Role::findOrCreate('partner', 'web');
        Role::findOrCreate('client', 'web');
        Role::findOrCreate('superadmin', 'web');
    }

    /** Dati come li consegna PartnerCreateForm::validate(). */
    private function data(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Marco',
            'lastName' => 'Galli',
            'businessName' => 'Agriturismo Le Corti',
            'email' => 'marco@example.com',
            'address' => 'Via Roma 1',
            'province' => 'BS',
            'zip' => '25100',
            'phone' => '+393331112222',
            'vat' => '01234567890',
            'taxCode' => 'GLLMRC80A01B157X',
            'paymentMode' => 'online',
            'paymentUrl' => '',
        ], $overrides);
    }

    private function service(): PartnerAccountService
    {
        return app(PartnerAccountService::class);
    }

    public function test_a_new_email_creates_an_active_partner_with_its_profile(): void
    {
        $created = $this->service()->create($this->data());

        $partner = $created->user;
        $this->assertFalse($created->promoted);
        $this->assertTrue($created->paymentModeSaved);
        $this->assertSame('marco@example.com', $partner->email);
        $this->assertTrue($partner->is_active);
        $this->assertTrue($partner->hasRole('partner'));
        $this->assertFalse($partner->hasRole('client'));
        $this->assertSame('Agriturismo Le Corti', $partner->partnerProfile->business_name);
        $this->assertTrue($partner->partnerProfile->requiresOnlinePayment());
        $this->assertNull($partner->partnerProfile->payment_url);
        // Nessun login: l'account si usa solo dopo aver scelto la password.
        $this->assertGuest();
    }

    public function test_the_email_is_stored_and_matched_in_lower_case(): void
    {
        $created = $this->service()->create($this->data(['email' => '  Marco@Example.COM ']));

        $this->assertSame('marco@example.com', $created->user->email);

        $this->expectException(PartnerAccountException::class);
        $this->service()->create($this->data(['email' => 'MARCO@example.com']));
    }

    public function test_a_legacy_mixed_case_address_is_found_too(): void
    {
        // Righe scritte prima della normalizzazione: su SQLite `=` distingue le maiuscole.
        $client = User::factory()->create(['email' => 'Giulia@Example.com']);
        $client->assignRole('client');

        $created = $this->service()->create($this->data(['email' => 'giulia@example.com']));

        $this->assertTrue($created->promoted);
        $this->assertTrue($created->user->is($client));
        $this->assertSame(1, User::query()->whereRaw('lower(email) = ?', ['giulia@example.com'])->count());
    }

    public function test_the_email_of_a_superadmin_is_refused(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('superadmin');

        try {
            $this->service()->create($this->data(['email' => 'admin@example.com']));
            $this->fail('Un amministratore non può diventare partner.');
        } catch (PartnerAccountException $e) {
            $this->assertSame(__('admin-people.partner_create.errors.superadmin'), $e->getMessage());
            $this->assertNull($e->user);
        }

        $this->assertFalse($admin->fresh()->hasRole('partner'));
        $this->assertDatabaseCount('partner_profiles', 0);
    }

    public function test_an_existing_partner_is_refused_and_named(): void
    {
        $partner = User::factory()->offlinePartner()->create(['email' => 'marco@example.com']);
        $partner->partnerProfile->update(['business_name' => 'Nome originale']);

        try {
            $this->service()->create($this->data());
            $this->fail('Un partner esistente non si ricrea.');
        } catch (PartnerAccountException $e) {
            $this->assertSame(__('admin-people.partner_create.errors.already_partner'), $e->getMessage());
            $this->assertTrue($e->user->is($partner));
        }

        $this->assertSame('Nome originale', $partner->partnerProfile->fresh()->business_name);
    }

    public function test_a_deactivated_account_is_refused_and_stays_deactivated(): void
    {
        $client = User::factory()->inactive()->create(['email' => 'marco@example.com']);
        $client->assignRole('client');

        try {
            $this->service()->create($this->data());
            $this->fail('Un account disattivato non si promuove.');
        } catch (PartnerAccountException $e) {
            $this->assertSame(__('admin-people.partner_create.errors.inactive'), $e->getMessage());
        }

        $this->assertFalse($client->fresh()->is_active);
        $this->assertFalse($client->fresh()->hasRole('partner'));
        $this->assertDatabaseCount('partner_profiles', 0);
    }

    public function test_an_anonymised_account_is_refused(): void
    {
        $client = User::factory()->create(['email' => 'marco@example.com']);
        $client->forceFill(['anonymized_at' => now()])->save();

        $this->expectException(PartnerAccountException::class);
        $this->expectExceptionMessage(__('admin-people.partner_create.errors.inactive'));

        $this->service()->create($this->data());
    }

    public function test_an_active_customer_is_promoted_and_stays_a_customer(): void
    {
        $client = User::factory()->create(['email' => 'marco@example.com', 'first_name' => 'Marcolino']);
        $client->assignRole('client');

        $created = $this->service()->create($this->data());

        $this->assertTrue($created->promoted);
        $this->assertTrue($created->user->is($client));
        $client->refresh();
        $this->assertTrue($client->hasRole('client'));
        $this->assertTrue($client->hasRole('partner'));
        $this->assertSame('Marco', $client->first_name);
        $this->assertSame('Agriturismo Le Corti', $client->partnerProfile->business_name);
        $this->assertSame(1, User::count());
    }

    public function test_an_on_site_partner_keeps_the_payment_link(): void
    {
        $created = $this->service()->create($this->data([
            'paymentMode' => 'on_site',
            'paymentUrl' => 'https://lecorti.example/prenota',
        ]));

        $profile = $created->user->partnerProfile->fresh();
        $this->assertFalse($profile->online_payment);
        $this->assertSame('https://lecorti.example/prenota', $profile->payment_url);
    }

    public function test_a_javascript_link_is_refused_before_anything_is_written(): void
    {
        try {
            $this->service()->create($this->data(['paymentMode' => 'on_site', 'paymentUrl' => 'javascript:alert(1)']));
            $this->fail('Il link deve essere http o https.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('paymentUrl', $e->errors());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('partner_profiles', 0);
    }

    public function test_a_customer_with_an_offline_profile_cannot_be_promoted_online_without_stripe(): void
    {
        // Ex partner a cui è stato tolto il ruolo: il profilo offline è rimasto.
        $client = User::factory()->create(['email' => 'marco@example.com']);
        $client->assignRole('client');
        PartnerProfile::factory()->offline()->for($client)->create();

        $this->expectException(PaymentModeException::class);

        try {
            $this->service()->create($this->data());
        } finally {
            $this->assertFalse($client->fresh()->hasRole('partner'));
        }
    }

    public function test_the_payment_mode_is_saved_after_the_registration_commits(): void
    {
        // P4 fa partire un job afterCommit da set(): chiamato dentro una
        // transazione esterna, un suo errore risalirebbe da create().
        $level = DB::transactionLevel();

        $this->mock(PartnerPaymentModeService::class, function (MockInterface $mock) use ($level): void {
            $mock->shouldReceive('set')->once()->andReturnUsing(function (PartnerProfile $profile) use ($level): PartnerProfile {
                $this->assertSame($level, DB::transactionLevel());

                return $profile;
            });
        });

        $this->service()->create($this->data(['paymentMode' => 'on_site', 'paymentUrl' => 'https://lecorti.example']));
    }

    public function test_a_failure_while_saving_the_payment_mode_does_not_undo_the_account(): void
    {
        Exceptions::fake();

        $this->mock(PartnerPaymentModeService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('set')->once()->andThrow(new RuntimeException('publish job failed'));
        });

        $created = $this->service()->create($this->data(['paymentMode' => 'on_site', 'paymentUrl' => 'https://lecorti.example']));

        $this->assertTrue($created->user->exists);
        $this->assertTrue($created->user->fresh()->hasRole('partner'));
        // Chi chiama deve poterlo dire all'admin: la modalità scelta non è stata salvata.
        $this->assertFalse($created->paymentModeSaved);
        Exceptions::assertReported(RuntimeException::class);
    }

    private function sentWelcome(string $email): PartnerWelcomeMail
    {
        return Mail::sent(PartnerWelcomeMail::class, fn (PartnerWelcomeMail $mail): bool => $mail->hasTo($email))->last();
    }

    private function tokenOf(string $url): string
    {
        return Str::afterLast((string) parse_url($url, PHP_URL_PATH), '/');
    }

    public function test_a_new_partner_gets_the_link_to_choose_the_password(): void
    {
        $created = $this->service()->create($this->data());
        $partner = $created->user;

        $this->assertTrue($created->welcomeSent);
        Mail::assertSent(PartnerWelcomeMail::class, 1);
        $mail = $this->sentWelcome('marco@example.com');

        $this->assertNotNull($mail->setPasswordUrl);
        $this->assertStringContainsString('welcome=1', $mail->setPasswordUrl);
        $this->assertStringContainsString('email=marco%40example.com', $mail->setPasswordUrl);
        $this->assertTrue(Password::broker('partner_welcome')->tokenExists($partner, $this->tokenOf($mail->setPasswordUrl)));
    }

    public function test_the_welcome_link_opens_the_welcome_page_and_sets_the_password(): void
    {
        $partner = $this->service()->create($this->data())->user;
        $url = $this->sentWelcome('marco@example.com')->setPasswordUrl;

        $this->get($url)
            ->assertOk()
            ->assertSee(__('auth-modal.partner_welcome.title'));

        Livewire::test(ResetPassword::class, ['token' => $this->tokenOf($url), 'email' => $partner->email, 'welcome' => true])
            ->set('password', 'password-scelta')
            ->set('passwordConfirm', 'password-scelta')
            ->call('save')
            ->assertSet('done', true);

        $this->assertTrue(Hash::check('password-scelta', $partner->fresh()->password));
    }

    public function test_a_promoted_customer_gets_the_mail_without_a_password_link(): void
    {
        $client = User::factory()->create(['email' => 'marco@example.com']);
        $client->assignRole('client');

        $this->service()->create($this->data(['email' => 'Marco@Example.com']));

        $this->assertNull($this->sentWelcome('marco@example.com')->setPasswordUrl);
        // Ha già la sua password: nessun token da consumare.
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'marco@example.com']);
    }

    public function test_the_welcome_mail_leaves_even_when_the_payment_mode_save_fails(): void
    {
        Exceptions::fake();

        $this->mock(PartnerPaymentModeService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('set')->once()->andThrow(new RuntimeException('publish job failed'));
        });

        $this->service()->create($this->data(['paymentMode' => 'on_site', 'paymentUrl' => 'https://lecorti.example']));

        Mail::assertSent(PartnerWelcomeMail::class, 1);
    }

    public function test_the_mail_is_sent_at_once_in_the_default_language(): void
    {
        app()->setLocale('en');
        $partner = User::factory()->offlinePartner()->create();

        $mail = new PartnerWelcomeMail($partner, 'https://animalamo.test/reimposta-password/abc');

        // Come ResetPasswordMail: senza un worker garantito, il benvenuto non deve aspettare la coda.
        $this->assertNotInstanceOf(ShouldQueue::class, $mail);
        $this->assertSame(LaravelLocalization::getDefaultLocale(), $mail->locale);
    }

    public function test_the_mail_renders_both_variants(): void
    {
        // La mail esce nella lingua di default: le attese si calcolano in quella, non in 'it'.
        $locale = LaravelLocalization::getDefaultLocale();
        $partner = User::factory()->offlinePartner()->create(['first_name' => 'Marco']);
        $partner->partnerProfile->update(['business_name' => 'Agriturismo Le Corti']);

        $withLink = new PartnerWelcomeMail($partner, 'https://animalamo.test/reimposta-password/abc?welcome=1');
        $withLink->assertHasSubject(__('partner.welcome_mail.subject', [], $locale));
        $withLink->assertSeeInHtml(__('partner.welcome_mail.set_password_cta', [], $locale));
        $withLink->assertSeeInHtml('https://animalamo.test/reimposta-password/abc?welcome=1', false);
        $withLink->assertSeeInHtml(__('partner.welcome_mail.expires', ['days' => 7], $locale));

        $promoted = new PartnerWelcomeMail($partner, null);
        $promoted->assertSeeInHtml(__('partner.welcome_mail.login_cta', [], $locale));
        $promoted->assertDontSeeInHtml(__('partner.welcome_mail.set_password_cta', [], $locale));
    }

    public function test_the_link_can_be_sent_again_but_not_twice_in_a_minute(): void
    {
        $partner = $this->service()->create($this->data())->user;
        $first = DB::table('password_reset_tokens')->where('email', $partner->email)->value('token');

        $this->service()->resendWelcome($partner);

        Mail::assertSent(PartnerWelcomeMail::class, 2);
        $this->assertNotSame($first, DB::table('password_reset_tokens')->where('email', $partner->email)->value('token'));

        try {
            $this->service()->resendWelcome($partner);
            $this->fail('Il secondo reinvio entro un minuto va rifiutato.');
        } catch (PartnerAccountException $e) {
            $this->assertSame(__('admin-people.partner_create.errors.throttled'), $e->getMessage());
        }

        Mail::assertSent(PartnerWelcomeMail::class, 2);
    }

    public function test_a_deactivated_partner_gets_no_new_link(): void
    {
        $partner = User::factory()->offlinePartner()->inactive()->create();

        $this->expectException(PartnerAccountException::class);
        $this->expectExceptionMessage(__('admin-people.partner_create.errors.inactive'));

        try {
            $this->service()->resendWelcome($partner);
        } finally {
            Mail::assertNothingSent();
        }
    }

    public function test_an_account_that_is_not_a_partner_gets_no_welcome_link(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->expectException(PartnerAccountException::class);
        $this->expectExceptionMessage(__('admin-people.partner_create.errors.not_partner'));

        try {
            $this->service()->resendWelcome($client);
        } finally {
            Mail::assertNothingSent();
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $client->email]);
        }
    }
}
