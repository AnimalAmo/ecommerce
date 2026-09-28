<?php

namespace Tests\Feature\Cart;

use App\Enums\OrderPaymentMode;
use App\Livewire\Commerce\Checkout;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\StripeGateway;
use Closure;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\Payment\FakePaymentGateway;
use Tests\TestCase;

/**
 * Difetto C10 (audit 27/09/2026, corretto il 28/09/2026): un venditore che
 * vende online ma che Stripe non fa incassare. Prima lo step 2 si apriva senza
 * element e senza «Paga ora», e da lì non si tornava indietro; ora ci si ferma
 * allo step 1, coi dati al loro posto, e l'avviso c'è già all'apertura.
 *
 * Il test della sezione «Difetto C10» di CheckoutPaymentTest copre il caso
 * base (online, mai collegato). Qui: le altre forme di «non pagabile», il
 * negativo del venditore pagabile, il ritorno pagabile, il regalo, e il ramo
 * `confirmBooking` in cui il partner torna online mentre il cliente è già
 * allo step 2 della conferma in struttura.
 *
 * Scritto dal tester il 28/09/2026.
 */
class CheckoutUnpayableSellerTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));
        Mail::fake();

        $this->seed(PaymentGatewaySeeder::class);
        app(PaymentGatewayService::class)->clearCache();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(StripeGateway::class, $this->gateway);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_allo_step_uno_l_avviso_c_e_prima_dei_dati(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->structureOf($this->unpayableSeller()));

        Livewire::test(Checkout::class)
            ->assertSet('step', 1)
            ->assertSee(__('checkout.seller_not_payable.notice'))
            ->assertSee(__('checkout.seller_not_payable.back_to_cart'))
            ->assertSeeHtml('href="'.route('carrello').'"');
    }

    /**
     * Il blocco sta tutto nel server: fermato allo step 1, il client non può
     * scriversi lo step 2 da solo (`$step` è #[Locked], difetto C1) e aprire
     * il pagamento che preparePaymentStep() ha rifiutato.
     */
    public function test_fermato_allo_step_uno_il_client_non_si_scrive_lo_step_due(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->structureOf($this->unpayableSeller()));

        $page = $this->assertBlockedAtStepOne(Livewire::test(Checkout::class));

        try {
            $page->set('step', 2);

            $this->fail('Atteso il rifiuto della scrittura su una property bloccata.');
        } catch (CannotUpdateLockedPropertyException) {
            // È il rifiuto che serve.
        }

        $page->call('processPayment')->assertNotDispatched('process-payment');
        $this->assertSame([], $this->gateway->initCalls);
    }

    /** Il negativo: chi può incassare non legge l'avviso e passa allo step 2. */
    public function test_un_venditore_pagabile_non_vede_l_avviso_e_avanza(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->structureOf(User::factory()->stripeConnected()->create()));

        Livewire::test(Checkout::class)
            ->assertDontSee(__('checkout.seller_not_payable.notice'))
            ->call('goToStep', 2)
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->assertSet('paymentUnavailable', false)
            ->assertNotDispatched('toast-show', $this->toast(__('checkout.seller_not_payable.toast')));

        $this->assertCount(1, $this->gateway->initCalls);
    }

    /**
     * Incassa ma non riceve bonifici (payouts spenti): canBePaid() è falso, e
     * il cliente pagherebbe un partner che al giorno 14 non verrebbe bonificato.
     */
    public function test_con_i_bonifici_spenti_si_resta_allo_step_uno(): void
    {
        $seller = User::factory()->create();
        PartnerProfile::factory()->connected()->for($seller)->create(['stripe_payouts_enabled' => false]);

        $this->actingAs($this->buyer());
        $this->addStructureLine($this->structureOf($seller));

        $this->assertBlockedAtStepOne(Livewire::test(Checkout::class));
    }

    /**
     * Venditore senza profilo partner: la modalità vale «online» (la regola di
     * forOwner), e un conto su cui incassare non c'è.
     */
    public function test_senza_profilo_partner_si_resta_allo_step_uno(): void
    {
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->structureOf(User::factory()->create()));

        $this->assertBlockedAtStepOne(Livewire::test(Checkout::class));
    }

    /**
     * Il blocco può essere temporaneo: quando il partner torna pagabile, lo
     * stesso checkout — con i dati già scritti — passa allo step 2 e apre la
     * sessione, senza il riquadro «pagamento non disponibile» rimasto dal
     * primo tentativo.
     */
    public function test_quando_il_venditore_torna_pagabile_il_secondo_tentativo_passa(): void
    {
        $seller = $this->unpayableSeller();
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->structureOf($seller));

        $page = $this->assertBlockedAtStepOne(Livewire::test(Checkout::class));

        $seller->partnerProfile->update([
            'stripe_account_id' => 'acct_tornatopagabile01',
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => true,
        ]);
        // Ogni chiamata Livewire è una richiesta nuova: il service scoped no.
        $this->app->forgetScopedInstances();

        $page->call('goToStep', 2)
            ->assertSet('step', 2)
            ->assertSet('paymentUnavailable', false)
            ->assertSet('clientSecret', 'cs_fake_secret')
            ->assertSet('firstName', 'Giulia');

        $this->assertCount(1, $this->gateway->initCalls);
        $this->assertSame('acct_tornatopagabile01', $this->gateway->initCalls[0]['context']['stripe_account_id']);
    }

    /** Il regalo passa dallo stesso step: stesso blocco, e il link torna al carrello regalo. */
    public function test_nel_flusso_regalo_si_resta_allo_step_uno(): void
    {
        $seller = $this->unpayableSeller();
        $box = SmartboxPackage::factory()->create(['user_id' => $seller->id, 'price_cents' => 21500]);

        $this->actingAs($this->buyer());
        app(CartManager::class)->addItem('smartbox_package', $box->id, [
            'animals' => ['cane' => 1],
            'gift' => ['dedication' => 'Marco', 'message' => 'Tanti auguri!'],
        ], true);

        $page = Livewire::withQueryParams(['regalo' => 1])->test(Checkout::class)
            ->assertSet('gift', true)
            ->assertSee(__('checkout.seller_not_payable.notice'))
            ->assertSeeHtml('href="'.route('carrello', ['regalo' => 1]).'"')
            ->set('recipientEmail', 'marco@example.com');

        $this->assertBlockedAtStepOne($page);
    }

    /**
     * Il ramo in struttura: il cliente è allo step 2 della conferma, e il
     * partner fra lo step 2 e il click passa a «online» senza essere pagabile.
     * Prima confirmBooking diceva «il partner ora accetta il pagamento online,
     * paga qui» e apriva uno step 2 senza «Paga ora». Ora niente ordine, niente
     * invito a pagare: il toast del venditore non pagabile e il riquadro.
     */
    public function test_la_conferma_in_struttura_verso_un_partner_tornato_online_non_pagabile(): void
    {
        config(['commerce.on_site_booking' => true]);

        $seller = User::factory()->offlinePartner()->create();
        $this->actingAs($this->buyer());
        $this->addStructureLine($this->structureOf($seller));

        $page = Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSet('step', 2)
            ->assertSet('paymentMode', OrderPaymentMode::OnSite->value);

        // Torna online senza Stripe: il pannello non lo permetterebbe
        // (canSwitchToOnline), ma il flag si sposta anche per altre strade.
        $seller->partnerProfile()->update(['online_payment' => true]);
        $this->app->forgetScopedInstances();

        $page->call('confirmBooking')
            ->assertNotDispatched('toast-show', $this->toast(__('checkout.on_site.mode_changed')))
            ->assertDispatched('toast-show', $this->toast(__('checkout.seller_not_payable.toast')))
            // Non più in struttura: un secondo click non prenota senza incasso.
            ->assertSet('paymentMode', OrderPaymentMode::Online->value)
            ->assertSet('checkoutToken', null)
            // E niente «Paga ora» su una sessione che non esiste.
            ->assertSet('paymentUnavailable', true)
            ->assertSet('clientSecret', null);

        $page->call('confirmBooking')
            ->call('processPayment')
            ->assertNotDispatched('process-payment');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], $this->gateway->initCalls);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /** Lo step 2 non si apre, i dati restano, il motivo è il venditore, nessuna sessione Stripe. */
    private function assertBlockedAtStepOne($page)
    {
        $page->call('goToStep', 2)
            ->assertHasNoErrors()
            ->assertSet('step', 1)
            ->assertSet('firstName', 'Giulia')
            ->assertSet('email', 'giulia@example.com')
            ->assertDispatched('toast-show', $this->toast(__('checkout.seller_not_payable.toast')));

        $this->assertSame([], $this->gateway->initCalls);

        return $page;
    }

    /** Flux::toast non finisce nell'HTML: è un evento `toast-show` con il testo in slots.text. */
    private function toast(string $text): Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    private function buyer(): User
    {
        return User::factory()->create([
            'first_name' => 'Giulia',
            'last_name' => 'Rossi',
            'email' => 'giulia@example.com',
            'phone' => '340 5738920',
        ]);
    }

    /** Online (default del profilo) ma senza Stripe: è il caso della segnalazione. */
    private function unpayableSeller(): User
    {
        $seller = User::factory()->create();
        PartnerProfile::factory()->for($seller)->create();

        return $seller;
    }

    private function structureOf(User $seller): Structure
    {
        return Structure::factory()->create(['user_id' => $seller->id, 'price_cents' => 10000]);
    }

    /** Riga struttura: 01/08 → 06/08 (5 notti), 2 adulti e 1 cane. */
    private function addStructureLine(Structure $structure): int|string
    {
        return app(CartManager::class)->addItem('structure', $structure->id, [
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-06',
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false)->key;
    }
}
