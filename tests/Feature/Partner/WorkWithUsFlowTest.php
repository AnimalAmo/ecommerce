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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class WorkWithUsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function fillApplication($component)
    {
        return $component
            ->set('form.password', 'password123')
            ->set('form.passwordConfirmation', 'password123')
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

    /** Candidatura aperta come quelle che il pannello invita (InboxService::invite). */
    private function invitedApplication(): PartnerApplication
    {
        return PartnerApplication::create([
            'first_name' => 'Susanna', 'last_name' => 'Rossi',
            'email' => 'susanna@example.com', 'phone' => '3498798828',
            'city' => 'Milano', 'business_name' => 'Hotel Rosovino',
            'role' => 'Titolare', 'offer_type' => 'Struttura ricettiva',
            'description' => 'Hotel pet friendly in centro a Milano.',
            'status' => PartnerApplication::STATUS_INVITED,
        ]);
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
        ];
    }

    /**
     * Iscrizione diretta (cliente, 06/10/2026): niente mail d'invito, l'account
     * partner nasce subito e si entra nella propria area. La candidatura resta,
     * registrata e da lavorare, per la cliente.
     */
    public function test_signing_up_creates_the_partner_and_logs_in_without_any_mail(): void
    {
        Mail::fake();

        $this->fillApplication(Livewire::test(WorkWithUs::class))
            // Il valore vero del select: preseleziona «Struttura» in «Crea servizio».
            ->set('form.offerType', __('partner.offer_accommodation'))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $user = User::where('email', 'susanna@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('partner'));
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame('Hotel Rosovino', $user->partnerProfile->business_name);
        $this->assertNull($user->partnerProfile->vat);
        $this->assertSame('struttura', $user->partnerProfile->registration_service);

        $application = PartnerApplication::sole();
        $this->assertSame(PartnerApplication::STATUS_REGISTERED, $application->status);
        $this->assertSame($user->id, $application->user_id);
        $this->assertNull($application->handled_at, 'La cliente la ritrova fra le candidature da lavorare.');

        Mail::assertNothingOutgoing();
    }

    public function test_the_dashboard_welcomes_the_new_partner_and_asks_for_the_missing_data(): void
    {
        $this->fillApplication(Livewire::test(WorkWithUs::class))->call('submit');

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee('Il tuo account partner è attivo')
            ->assertSee('Completa il profilo: mancano partita iva, codice fiscale, indirizzo.');
    }

    public function test_an_email_already_registered_is_refused_for_a_visitor(): void
    {
        User::factory()->create(['email' => 'susanna@example.com']);

        $this->fillApplication(Livewire::test(WorkWithUs::class))
            ->call('submit')
            ->assertHasErrors(['form.email' => 'unique']);

        $this->assertGuest();
        $this->assertSame(0, PartnerApplication::count());
    }

    public function test_the_password_must_be_long_enough_and_repeated(): void
    {
        $this->fillApplication(Livewire::test(WorkWithUs::class))
            ->set('form.password', 'corta')
            ->set('form.passwordConfirmation', 'altra')
            ->call('submit')
            ->assertHasErrors(['form.password' => 'min', 'form.passwordConfirmation' => 'same']);

        $this->assertGuest();
    }

    /**
     * Regressione dal campo: la candidatura reale "info@…​.comm" (refuso nel
     * TLD) superava la validazione, passava a INVITED e moriva in Mailgun con
     * "No MX" — il candidato restava in attesa di un invito mai partito.
     * Il test interroga il DNS davvero: `.comm` non è un TLD esistente.
     */
    public function test_the_application_rejects_an_email_whose_domain_does_not_exist(): void
    {
        // Alcune reti domestiche (router TIM: search domain homenet.telecomitalia.it)
        // rispondono con un wildcard a *qualsiasi* nome: lì NXDOMAIN non esiste e
        // la regola `dns` non può essere osservata. Meglio saltare che avere un
        // rosso che dipende dal Wi-Fi.
        // La sonda usa lo stesso TLD inesistente del caso reale: `.invalid` non
        // serve, systemd-resolved lo tratta come speciale e non ci applica il
        // search domain, quindi non rivelerebbe il wildcard.
        if (checkdnsrr('nxdomain-probe-'.uniqid().'.comm', 'A')) {
            $this->markTestSkipped('Il resolver di questa rete risolve anche i domini inesistenti.');
        }

        Mail::fake();

        $this->fillApplication(Livewire::test(WorkWithUs::class))
            ->set('form.email', 'info@babaresidences.comm')
            ->call('submit')
            ->assertHasErrors(['form.email']);

        $this->assertSame(0, PartnerApplication::count());
        Mail::assertNotQueued(PartnerInvitationMail::class);
    }

    /**
     * Ogni invio crea un account partner: senza tetto un bot riempirebbe il
     * pannello di account finti. Stesso rimedio già usato in RegisterModal.
     */
    public function test_the_application_is_rate_limited_per_ip(): void
    {
        Mail::fake();

        for ($i = 1; $i <= 5; $i++) {
            $this->fillApplication(Livewire::test(WorkWithUs::class))
                ->set('form.email', "hotel{$i}@example.com")
                ->call('submit')
                ->assertHasNoErrors();

            auth()->logout();
        }

        $this->fillApplication(Livewire::test(WorkWithUs::class))
            ->set('form.email', 'hotel6@example.com')
            ->call('submit')
            ->assertHasErrors('form.email');

        $this->assertSame(5, PartnerApplication::count());
        $this->assertSame(5, User::role('partner')->count());
    }

    /** Un modulo compilato male non consuma il budget: solo le iscrizioni davvero fatte. */
    public function test_a_rejected_submit_does_not_burn_the_rate_limit(): void
    {
        Mail::fake();

        for ($i = 1; $i <= 6; $i++) {
            Livewire::test(WorkWithUs::class)->call('submit')->assertHasErrors('form.email');
        }

        $this->fillApplication(Livewire::test(WorkWithUs::class))
            ->call('submit')
            ->assertHasNoErrors();
    }

    public function test_the_application_requires_the_mandatory_fields(): void
    {
        Mail::fake();

        Livewire::test(WorkWithUs::class)
            ->call('submit')
            ->assertHasErrors(['form.firstName', 'form.email', 'form.description', 'form.password']);

        $this->assertSame(0, PartnerApplication::count());
        Mail::assertNothingSent();
    }

    /** Stessa regressione dello step 1: senza slot d'errore il tasto sembra morto. */
    public function test_the_application_shows_the_validation_messages(): void
    {
        Mail::fake();

        Livewire::test(WorkWithUs::class)
            ->call('submit')
            ->assertSee([
                'Inserisci il nome.',
                'Inserisci il cognome.',
                'Inserisci la città.',
                'Inserisci la ragione sociale.',
                'Inserisci il tuo ruolo.',
                'Inserisci il tipo di offerta.',
                'Inserisci una descrizione.',
            ]);
    }

    public function test_the_signed_invitation_link_prefills_step_1(): void
    {
        $application = $this->invitedApplication();

        $link = URL::signedRoute('partner.register', ['application' => $application->id]);

        $this->get($link)
            ->assertOk()
            ->assertSee('Susanna')
            ->assertSee('Hotel Rosovino')
            ->assertSee('susanna@example.com');
    }

    /**
     * Il caso che la cliente vedeva come "l'invito non funziona": l'albergatore
     * apre il link dal browser dove è già loggato col suo account personale.
     * Prima l'iscrizione proseguiva in silenzio e il partner nasceva su
     * quell'account, mentre l'indirizzo invitato non diventava mai una login.
     */
    public function test_an_invitation_opened_from_another_account_is_blocked(): void
    {
        $application = $this->invitedApplication();

        $other = User::factory()->create(['email' => 'mario.personale@example.com']);
        $link = URL::signedRoute('partner.register', ['application' => $application->id]);

        $this->actingAs($other)->get($link)
            ->assertOk()
            ->assertSee('susanna@example.com')      // l'invito è per questo indirizzo
            ->assertDontSee('Hotel Rosovino');      // e i dati non vengono precompilati

        $this->assertSame('susanna@example.com', session('partner_registration.invitation_for'));

        // Il blocco non è solo grafica, e non si toglie riaprendo la pagina
        // senza il link: il submit rifiuta anche se lo si chiama a mano.
        Livewire::actingAs($other)->test(PartnerRegisterStep1::class)
            ->assertSet('invitationFor', 'susanna@example.com')
            ->call('submit')
            ->assertHasErrors('form.email')
            ->assertNoRedirect();
    }

    /** Uscire e rientrare dall'indirizzo invitato è la via d'uscita promessa dal messaggio. */
    public function test_the_block_falls_away_once_the_invited_address_signs_in(): void
    {
        $application = $this->invitedApplication();

        $other = User::factory()->create(['email' => 'mario.personale@example.com']);
        $this->actingAs($other)->get(URL::signedRoute('partner.register', ['application' => $application->id]));

        $invited = User::factory()->create(['email' => 'susanna@example.com']);

        Livewire::actingAs($invited)->test(PartnerRegisterStep1::class)
            ->assertSet('invitationFor', '');

        $this->assertNull(session('partner_registration.invitation_for'));
    }

    /** Stesso account dell'invito: nessun blocco, prefill normale. */
    public function test_an_invitation_opened_from_its_own_account_still_prefills(): void
    {
        $application = $this->invitedApplication();

        $owner = User::factory()->create(['email' => 'susanna@example.com']);

        $this->actingAs($owner)
            ->get(URL::signedRoute('partner.register', ['application' => $application->id]))
            ->assertOk()
            ->assertSee('Hotel Rosovino');
    }

    public function test_an_unsigned_application_param_does_not_prefill(): void
    {
        $application = $this->invitedApplication();

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

    /**
     * La segnalazione del cliente: chiusa l'iscrizione, la dashboard salutava
     * tutti con il nome del mockup. Qui ci si iscrive con un nome diverso da
     * quello dei fixture e si SEGUE il redirect fino in dashboard.
     */
    public function test_the_dashboard_after_registration_greets_the_new_partner(): void
    {
        $this->seed(RoleSeeder::class);
        session(['partner_registration.step1' => array_merge($this->step1Data(), [
            'firstName' => 'Marco',
            'lastName' => 'Verdi',
            'email' => 'marco@example.com',
        ])]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertRedirect(route('partner.dashboard'));

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(__('partner.dashboard.welcome', ['name' => 'Marco']))
            ->assertDontSee('Susanna');
    }

    public function test_step_2_without_step_1_data_returns_to_step_1(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertRedirect(route('partner.register'));

        $this->assertSame(0, User::count());
    }

    /** Il dettaglio del rimbalzo e del recupero sta in PartnerEmailConflictTest. */
    public function test_step_2_rejects_an_email_already_registered(): void
    {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'susanna@example.com']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertRedirect(route('partner.register'));

        $this->assertSame(1, User::count());
        $this->assertSame('susanna@example.com', session('partner_registration.email_conflict'));
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
