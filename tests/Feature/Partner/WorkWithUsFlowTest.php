<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use App\Livewire\Partner\Registration\WorkWithUs;
use App\Mail\PartnerInvitationMail;
use App\Models\Partner\PartnerApplication;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class WorkWithUsFlowTest extends TestCase
{
    use RefreshDatabase;

    private function fillApplication($component)
    {
        return $component
            ->set('form.firstName', 'Susanna')
            ->set('form.lastName', 'Rossi')
            ->set('form.email', 'susanna@example.com')
            ->set('form.phone', '3498798828')
            ->set('form.city', 'Milano')
            ->set('form.businessName', 'Hotel Rosovino')
            ->set('form.role', 'Titolare')
            ->set('form.offerType', 'Struttura ricettiva')
            ->set('form.description', 'Hotel pet friendly in centro a Milano.');
    }

    private function step1Data(): array
    {
        return [
            'firstName' => 'Susanna',
            'lastName' => 'Rossi',
            'businessName' => 'Hotel Rosovino',
            'email' => 'susanna@example.com',
            'address' => 'Via C. Pacini 19',
            'province' => 'MI',
            'zip' => '20131',
            'phone' => '3498798828',
            'vat' => '86334519757',
            'taxCode' => 'SSNNRSS98A39T582I',
            'pec' => 'susanna@pec.it',
            'sdi' => 'SUBM70N',
        ];
    }

    public function test_submitting_the_application_persists_it_and_sends_the_invitation(): void
    {
        Mail::fake();

        $this->fillApplication(Livewire::test(WorkWithUs::class))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('work-with-us.thanks'));

        $application = PartnerApplication::firstOrFail();
        $this->assertSame('susanna@example.com', $application->email);
        $this->assertSame(PartnerApplication::STATUS_INVITED, $application->status);
        $this->assertNotNull($application->invited_at);

        Mail::assertSent(PartnerInvitationMail::class, function (PartnerInvitationMail $mail) use ($application): bool {
            return $mail->hasTo('susanna@example.com')
                && $mail->application->is($application)
                && str_contains($mail->link, 'application='.$application->id)
                && str_contains($mail->link, 'signature=');
        });
    }

    public function test_the_application_requires_the_mandatory_fields(): void
    {
        Mail::fake();

        Livewire::test(WorkWithUs::class)
            ->call('submit')
            ->assertHasErrors(['form.firstName', 'form.email', 'form.description']);

        $this->assertSame(0, PartnerApplication::count());
        Mail::assertNothingSent();
    }

    public function test_the_signed_invitation_link_prefills_step_1(): void
    {
        Mail::fake();
        $this->fillApplication(Livewire::test(WorkWithUs::class))->call('submit');
        $application = PartnerApplication::firstOrFail();

        $link = URL::signedRoute('partner.register', ['application' => $application->id]);

        $this->get($link)
            ->assertOk()
            ->assertSee('Susanna')
            ->assertSee('Hotel Rosovino')
            ->assertSee('susanna@example.com');
    }

    public function test_an_unsigned_application_param_does_not_prefill(): void
    {
        Mail::fake();
        $this->fillApplication(Livewire::test(WorkWithUs::class))->call('submit');
        $application = PartnerApplication::firstOrFail();

        $this->get(route('partner.register', ['application' => $application->id]))
            ->assertOk()
            ->assertDontSee('Hotel Rosovino');
    }

    public function test_completing_step_2_creates_the_partner_account(): void
    {
        $this->seed(RoleSeeder::class);
        session([
            'partner_registration.step1' => $this->step1Data(),
            'partner_registration.application_id' => PartnerApplication::create([
                'first_name' => 'Susanna', 'last_name' => 'Rossi',
                'email' => 'susanna@example.com', 'phone' => '3498798828',
                'city' => 'Milano', 'business_name' => 'Hotel Rosovino',
                'role' => 'Titolare', 'offer_type' => 'Struttura ricettiva',
                'description' => 'Hotel pet friendly.',
                'status' => PartnerApplication::STATUS_INVITED,
            ])->id,
        ]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $user = User::where('email', 'susanna@example.com')->firstOrFail();
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('partner'));
        $this->assertSame('Hotel Rosovino', $user->partnerProfile->business_name);
        $this->assertSame('86334519757', $user->partnerProfile->vat);
        $this->assertSame(PartnerApplication::STATUS_REGISTERED, PartnerApplication::first()->status);
        $this->assertAuthenticatedAs($user);

        // La sessione di registrazione è stata consumata.
        $this->assertNull(session('partner_registration.step1'));
    }

    public function test_step_2_without_step_1_data_returns_to_step_1(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertRedirect(route('partner.register'));

        $this->assertSame(0, User::count());
    }

    public function test_step_2_rejects_an_email_already_registered(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'susanna@example.com']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertHasErrors('service');

        $this->assertSame(1, User::count());
    }

    public function test_step_1_keeps_the_entered_data_when_coming_back(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Verdi')
            ->set('form.businessName', 'B&B Le Palme')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.zip', '35100')
            ->set('form.phone', '3331234567')
            ->set('form.vat', '12345678901')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->set('form.pec', 'palme@pec.it')
            ->set('form.sdi', 'ABCDEF1')
            ->call('submit');

        $this->get(route('partner.register'))
            ->assertOk()
            ->assertSee('B&B Le Palme');
    }

    public function test_partner_pages_send_the_noindex_header(): void
    {
        $this->get(route('partner.register'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->actingAsActivePartner();
        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // Le pagine pubbliche B2C restano indicizzabili.
        $this->get(route('work-with-us'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }
}
