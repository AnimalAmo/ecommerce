<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Registration\PartnerRegisterStep1;
use App\Livewire\Partner\Registration\PartnerRegisterStep2;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use App\Services\Partner\RegisterPartnerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

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
        ], $overrides);
    }

    public function test_step_1_page_renders(): void
    {
        $this->get(route('partner.register'))
            ->assertOk()
            ->assertSee(__('partner.register.heading'))
            ->assertSee(__('partner.register.step'))
            ->assertSee(__('partner.register.next'));
    }

    /**
     * PEC e codice SDI tolti su richiesta della cliente (18/09/2026): prima
     * dallo step 1, poi anche dall'area partner. Sono dati di fatturazione
     * elettronica e non servono per iscriversi. Le colonne restano a database
     * (nullable) con i valori dei partner già registrati: qui si verifica che
     * nessuna schermata li chieda più.
     */
    public function test_step_1_does_not_ask_for_the_e_invoicing_fields(): void
    {
        $this->get(route('partner.register'))
            ->assertOk()
            ->assertDontSee('PEC')
            ->assertDontSee('SDI');

        Livewire::test(PartnerRegisterStep1::class)
            ->call('submit')
            ->assertHasNoErrors(['form.pec', 'form.sdi']);
    }

    public function test_step_1_requires_the_mandatory_fields(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->call('submit')
            ->assertHasErrors(['form.firstName', 'form.email', 'form.vat', 'form.taxCode'])
            ->assertNoRedirect();
    }

    public function test_step_1_rejects_a_non_numeric_cap(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.zip', 'abc')
            ->call('submit')
            ->assertHasErrors(['form.zip']);
    }

    public function test_step_1_advances_to_step_2_when_valid(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.zip', '35100')
            ->set('form.phone', '3331234567')
            ->set('form.vat', '12345678901')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.register.step2'));
    }

    /**
     * Regressione: i campi erano composti a mano (flux:field + flux:label +
     * flux:input) e Flux inietta lo slot d'errore solo quando `label` è una
     * PROP del controllo. Gli errori finivano nell'error bag ma non in pagina:
     * un submit rifiutato ridisegnava il form identico e "Prosegui" sembrava
     * un tasto morto. Non basta assertHasErrors — il messaggio deve USCIRE.
     */
    public function test_step_1_shows_the_error_of_the_only_field_left_out(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.zip', '35100')
            ->set('form.phone', '3331234567')
            ->set('form.vat', '12345678901')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            // La provincia è l'unica non scelta: è il caso segnalato dalla cliente.
            ->call('submit')
            ->assertHasErrors('form.province')
            ->assertNoRedirect()
            ->assertSee('Inserisci la provincia.');
    }

    /** Ogni campo deve avere il suo slot: un solo buco riapre il tasto morto. */
    public function test_step_1_shows_a_message_for_every_field(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->call('submit')
            ->assertSee([
                'Inserisci il nome.',
                'Inserisci il cognome.',
                'Inserisci la ragione sociale.',
                'Inserisci l\'email.',
                'Inserisci l\'indirizzo.',
                'Inserisci la provincia.',
                'Inserisci il CAP.',
                'Inserisci il numero di cellulare.',
                'Inserisci la partita IVA.',
                'Inserisci il codice fiscale.',
            ]);
    }

    /** I messaggi dei dati fiscali dicono il limite, non "Valore troppo lungo." */
    public function test_step_1_explains_the_length_of_the_tax_fields(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.zip', '351')
            ->set('form.vat', 'PARTITA IVA 12345678901')
            ->call('submit')
            ->assertSee([
                'Il CAP deve avere 5 cifre.',
                'La partita IVA non può superare i 13 caratteri.',
            ]);
    }

    public function test_step_2_page_renders_the_three_service_options(): void
    {
        $this->get(route('partner.register.step2'))
            ->assertOk()
            ->assertSee(__('partner.register2.step'))
            ->assertSee(__('partner.register2.struttura_title'))
            ->assertSee(__('partner.register2.attivita_title'))
            ->assertSee(__('partner.register2.servizi_title'))
            ->assertSee(__('partner.register2.submit'));
    }

    public function test_step_2_requires_a_service(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->call('createAccount')
            ->assertHasErrors('service');
    }

    public function test_step_2_rejects_an_unknown_service(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'qualcosaltro')
            ->call('createAccount')
            ->assertHasErrors('service');
    }

    public function test_step_2_accepts_a_valid_selection(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'attivita')
            ->call('createAccount')
            ->assertHasNoErrors();
    }

    public function test_step_2_asks_how_the_partner_wants_to_be_paid(): void
    {
        $this->get(route('partner.register.step2'))
            ->assertOk()
            ->assertSee(__('partner.register2.payment_mode.label'))
            ->assertSee(__('partner.register2.payment_mode.online_title'))
            ->assertSee(__('partner.register2.payment_mode.on_site_title'));

        // Preselezionato online: chi non sceglie resta come i partner di prima.
        Livewire::test(PartnerRegisterStep2::class)->assertSet('paymentMode', 'online');
    }

    public function test_step_2_rejects_an_unknown_payment_mode(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->set('paymentMode', 'bonifico')
            ->call('createAccount')
            ->assertHasErrors('paymentMode')
            ->assertSee('Valore non valido.');
    }

    /** Solo da richiesta manomessa, ma il testo deve restare leggibile: niente "payment mode". */
    public function test_step_2_asks_again_for_a_missing_payment_mode(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->set('paymentMode', '')
            ->call('createAccount')
            ->assertHasErrors(['paymentMode' => 'required'])
            ->assertSee('Scegli la modalità di pagamento.');
    }

    public function test_a_new_partner_is_online_by_default(): void
    {
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $this->assertTrue(User::where('email', 'susanna@example.com')->firstOrFail()->partnerProfile->online_payment);
    }

    public function test_a_new_partner_can_choose_to_be_paid_directly(): void
    {
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->set('paymentMode', 'on_site')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $profile = User::where('email', 'susanna@example.com')->firstOrFail()->partnerProfile;
        $this->assertFalse($profile->online_payment);
        $this->assertTrue($profile->canPublish());

        // Consumata con il resto della sessione di registrazione.
        $this->assertNull(session('partner_registration.payment_mode'));
    }

    public function test_a_promoted_client_without_a_profile_gets_the_chosen_mode(): void
    {
        $client = User::factory()->create(['email' => 'susanna@example.com']);
        $client->syncRoles(['client']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::actingAs($client)
            ->test(PartnerRegisterStep2::class)
            ->set('service', 'attivita')
            ->set('paymentMode', 'on_site')
            ->call('createAccount')
            ->assertRedirect(route('partner.dashboard'));

        $this->assertFalse($client->fresh()->partnerProfile->online_payment);
    }

    /**
     * Rifare l'iscrizione su un profilo esistente non cambia la modalità:
     * riportare online un partner senza Stripe con schede vive le
     * renderebbe invendibili.
     */
    public function test_registering_again_does_not_change_an_existing_mode(): void
    {
        $partner = User::factory()->create(['email' => 'susanna@example.com']);
        $partner->syncRoles(['client']);
        PartnerProfile::factory()->offline()->for($partner)->create();
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::actingAs($partner)
            ->test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->set('paymentMode', 'online')
            ->call('createAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $this->assertFalse($partner->fresh()->partnerProfile->online_payment);
    }

    public function test_the_registrar_writes_the_mode_only_on_a_new_profile(): void
    {
        $registrar = app(RegisterPartnerAccount::class);

        $new = $registrar->register($this->step1Data(['email' => 'nuovo@example.com']) + ['onlinePayment' => false]);
        $this->assertFalse($new->partnerProfile->fresh()->online_payment);

        $existing = User::factory()->create(['email' => 'esistente@example.com']);
        PartnerProfile::factory()->for($existing)->create();
        $registrar->register($this->step1Data(['email' => 'esistente@example.com']) + ['onlinePayment' => false], $existing);
        $this->assertTrue($existing->fresh()->partnerProfile->online_payment);

        // Senza la chiave (chiamanti di prima) si resta online.
        $legacy = $registrar->register($this->step1Data(['email' => 'legacy@example.com']));
        $this->assertTrue($legacy->partnerProfile->fresh()->online_payment);
    }

    public function test_the_choice_survives_the_email_conflict_bounce(): void
    {
        User::factory()->create(['email' => 'susanna@example.com']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->set('paymentMode', 'on_site')
            ->call('createAccount')
            ->assertRedirect(route('partner.register'));

        $this->assertSame('on_site', session('partner_registration.payment_mode'));

        Livewire::test(PartnerRegisterStep2::class)->assertSet('paymentMode', 'on_site');
    }

    public function test_the_choice_survives_a_disabled_account(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'susanna@example.com']);
        $user->syncRoles(['client']);
        session(['partner_registration.step1' => $this->step1Data()]);

        Livewire::actingAs($user)
            ->test(PartnerRegisterStep2::class)
            ->set('service', 'struttura')
            ->set('paymentMode', 'on_site')
            ->call('createAccount')
            ->assertSet('accountInactive', true)
            ->assertNoRedirect();

        $this->assertSame('on_site', session('partner_registration.payment_mode'));
    }
}
