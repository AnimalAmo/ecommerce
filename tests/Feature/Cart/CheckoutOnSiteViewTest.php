<?php

namespace Tests\Feature\Cart;

use App\Events\OnSiteOrderConfirmed;
use App\Livewire\Commerce\Checkout;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Payment\StripeGateway;
use App\Support\Format;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event as Events;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\Payment\FakePaymentGateway;
use Tests\TestCase;

/**
 * Cosa vede il cliente nel ramo in struttura: riquadro col partner e
 * l'importo, nessun elemento Stripe, stepper "Conferma" e step 3 dedicato.
 * Più la dedica/messaggio dello step 3, finora scritti a mano nella vista.
 */
class CheckoutOnSiteViewTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Carbon::setTestNow(Carbon::create(2026, 7, 15, 12, 0, 0));

        Mail::fake();
        Events::fake([OnSiteOrderConfirmed::class]);

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

    public function test_step_two_shows_the_on_site_card_without_stripe(): void
    {
        $this->actingAs(User::factory()->create());
        $this->addStructureLine($this->offlineStructure('https://example.com/paga'));

        Livewire::test(Checkout::class)
            // Il tab dice "Conferma" già allo step 1: la modalità si legge dal venditore.
            ->assertViewHas('steps', fn (array $steps): bool => $steps[2] === __('checkout.on_site.step_label'))
            ->call('goToStep', 2)
            ->assertViewHas('steps', fn (array $steps): bool => $steps[2] === __('checkout.on_site.step_label'))
            ->assertSee(__('checkout.on_site.title'))
            ->assertSee(__('checkout.on_site.notice', [
                'partner' => 'Agriturismo Il Faro',
                'amount' => Format::money(50000),
            ]))
            ->assertSee(__('checkout.on_site.pay_on_website'))
            ->assertSee('https://example.com/paga')
            ->assertSee(__('checkout.on_site.confirm_cta'))
            ->assertSeeHtml('wire:click="confirmBooking"')
            // Nessun componente Stripe: Stripe.js non si carica nemmeno.
            ->assertDontSeeHtml('paymentWatchdog')
            ->assertDontSeeHtml('stripePayment(')
            ->assertDontSee(__('checkout.ui.select_payment_method'))
            ->assertDontSee(__('checkout.ui.pay_now'));
    }

    public function test_without_a_payment_url_the_link_is_not_shown(): void
    {
        $this->actingAs(User::factory()->create());
        $this->addStructureLine($this->offlineStructure(null));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertSee(__('checkout.on_site.confirm_cta'))
            ->assertDontSee(__('checkout.on_site.pay_on_website'));
    }

    public function test_an_unsafe_payment_url_is_never_printed(): void
    {
        $this->actingAs(User::factory()->create());
        // Scritto dritto sul profilo, saltando la validazione del service: è la
        // difesa in profondità per le scritture che non passano da lì.
        $this->addStructureLine($this->offlineStructure('javascript:alert(1)'));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertDontSeeHtml('javascript:')
            ->assertDontSee(__('checkout.on_site.pay_on_website'))
            ->assertSee(__('checkout.on_site.confirm_cta'));
    }

    public function test_step_three_shows_the_on_site_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $structure = $this->offlineStructure('https://example.com/paga');
        $this->addStructureLine($structure);

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->call('confirmBooking')
            ->assertSet('step', 3)
            ->assertSee(__('checkout.on_site.thank_you'))
            ->assertSee(__('checkout.on_site.thank_you_sub'))
            ->assertSee($structure->name)
            ->assertDontSee(__('checkout.ui.thank_you'))
            ->assertDontSee(__('checkout.ui.check_email'));
    }

    public function test_an_online_step_two_still_mounts_stripe(): void
    {
        $this->actingAs(User::factory()->create());
        $this->addStructureLine(Structure::factory()->create([
            'user_id' => User::factory()->stripeConnected()->create()->id, 'price_cents' => 10000]));

        Livewire::test(Checkout::class)
            ->call('goToStep', 2)
            ->assertViewHas('steps', fn (array $steps): bool => $steps[2] === __('checkout.ui.step_payment'))
            ->assertSeeHtml('paymentWatchdog')
            ->assertSee(__('checkout.ui.select_payment_method'))
            ->assertDontSee(__('checkout.on_site.confirm_cta'));
    }

    public function test_gift_dedication_and_message_at_step_three_come_from_the_lang_keys(): void
    {
        // Carica il gruppo prima di sovrascrivere due chiavi, o addLines lo
        // segnerebbe come già caricato e le altre chiavi sparirebbero.
        __('checkout.ui.total');
        app('translator')->addLines([
            'checkout.ui.dedicated_to' => 'Per: :name',
            'checkout.ui.message' => 'Nota: :message',
        ], 'it');

        $this->actingAs(User::factory()->create());
        $seller = User::factory()->stripeConnected()->create();
        app(CartManager::class)->addItem('smartbox_package', SmartboxPackage::factory()->create([
            'user_id' => $seller->id, 'price_cents' => 21500])->id, [
                'animals' => ['cane' => 1],
                'gift' => ['dedication' => 'Marco', 'message' => 'Tanti auguri!'],
            ], true);

        Livewire::withQueryParams(['regalo' => 1])->test(Checkout::class)
            ->set('recipientEmail', 'marco@example.com')
            ->call('goToStep', 2)
            ->call('handlePaymentCallback', ['payment_intent_id' => 'pi_fake_1'])
            ->assertSet('step', 3)
            ->assertSee('Per: Marco')
            ->assertSee('Nota: Tanti auguri!')
            ->assertDontSee('Dedicato a:')
            ->assertDontSee('Messaggio:');
    }

    private function offlineStructure(?string $paymentUrl): Structure
    {
        $seller = User::factory()->offlinePartner()->create();
        $seller->partnerProfile()->update([
            'business_name' => 'Agriturismo Il Faro',
            'payment_url' => $paymentUrl,
        ]);

        return Structure::factory()->create(['user_id' => $seller->id, 'price_cents' => 10000]);
    }

    /** Riga struttura: 01/08 → 06/08 (5 notti = 500 €), 2 adulti e 1 cane. */
    private function addStructureLine(Structure $structure): void
    {
        app(CartManager::class)->addItem('structure', $structure->id, [
            'check_in' => '2026-08-01',
            'check_out' => '2026-08-06',
            'guests' => ['adulti' => 2, 'ragazzi' => 0, 'bambini' => 0],
            'animals' => ['cane' => 1],
        ], false);
    }
}
