<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfilePayment;
use App\Models\Partner\PartnerProfile;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerProfilePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    private function toast(string $text): Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    public function test_page_renders_the_payment_fields(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile.payment'))
            ->assertOk()
            ->assertSee(__('partner.profile.payment_heading'))
            ->assertSee(__('partner.profile.account_holder'))
            ->assertSee(__('partner.profile.iban'))
            ->assertSee(__('partner.profile.bic'));
    }

    public function test_it_rehydrates_the_saved_payment_data(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create(['iban' => 'IT037400000000007382', 'account_holder' => 'Susanna Rossi']);

        Livewire::test(PartnerProfilePayment::class)
            ->assertSet('form.iban', 'IT037400000000007382')
            ->assertSet('form.accountHolder', 'Susanna Rossi');
    }

    public function test_save_requires_the_fields(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->call('save')
            ->assertHasErrors(['form.accountHolder', 'form.iban', 'form.bic']);
    }

    public function test_save_persists_the_payment_data(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->set('form.accountHolder', 'Susanna Rossi')
            ->set('form.iban', 'IT60X0542811101000000123456')
            ->set('form.bic', 'UNCRITMM')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'account_holder' => 'Susanna Rossi',
            'iban' => 'IT60X0542811101000000123456',
        ]);
    }

    /**
     * Regressione: i campi IBAN/BIC erano composti a mano senza flux:error. Un
     * salvataggio rifiutato ridisegnava il form identico. Il messaggio deve
     * uscire in pagina, non basta l'error bag.
     */
    public function test_save_shows_the_errors_of_the_bank_fields(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->call('save')
            ->assertSee([
                'Inserisci il titolare del conto.',
                'Inserisci l\'IBAN.',
                'Inserisci il BIC.',
            ]);
    }

    public function test_it_shows_the_saved_payment_mode(): void
    {
        $partner = $this->actingAsOfflinePartner();
        $partner->partnerProfile->update(['payment_url' => 'https://www.hotelrosovino.it']);

        Livewire::test(PartnerProfilePayment::class)
            ->assertSee(__('partner.payment_mode.section'))
            ->assertSet('paymentMode', 'on_site')
            ->assertSet('paymentUrl', 'https://www.hotelrosovino.it');
    }

    public function test_an_online_partner_switches_to_on_site_payment(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        Livewire::test(PartnerProfilePayment::class)
            ->assertSet('paymentMode', 'online')
            ->set('paymentMode', 'on_site')
            ->set('paymentUrl', 'https://www.hotelrosovino.it')
            ->call('savePaymentMode')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', $this->toast(__('partner.payment_mode.saved')));

        $profile = $partner->partnerProfile->fresh();
        $this->assertFalse($profile->online_payment);
        $this->assertSame('https://www.hotelrosovino.it', $profile->payment_url);
    }

    public function test_an_offline_partner_without_stripe_cannot_go_back_online(): void
    {
        $partner = $this->actingAsOfflinePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->assertSee(__('partner.payment_mode.online_needs_stripe'))
            ->set('paymentMode', 'online')
            ->call('savePaymentMode')
            ->assertHasErrors('paymentMode')
            ->assertSee(__('partner.payment_mode.errors.stripe_required'))
            ->assertNotDispatched('toast-show');

        $this->assertFalse($partner->partnerProfile->fresh()->online_payment);
    }

    public function test_an_offline_partner_with_stripe_goes_back_online(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->connected()->for($partner)->create(['online_payment' => false]);

        Livewire::test(PartnerProfilePayment::class)
            ->assertDontSee(__('partner.payment_mode.online_needs_stripe'))
            ->set('paymentMode', 'online')
            ->call('savePaymentMode')
            ->assertHasNoErrors();

        $this->assertTrue($partner->partnerProfile->fresh()->online_payment);
    }

    public function test_an_online_partner_without_stripe_is_not_locked_out(): void
    {
        // È già online: la scelta non va disabilitata, e salvare il link non chiede Stripe.
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        Livewire::test(PartnerProfilePayment::class)
            ->assertDontSee(__('partner.payment_mode.online_needs_stripe'))
            ->set('paymentUrl', 'https://www.hotelrosovino.it')
            ->call('savePaymentMode')
            ->assertHasNoErrors();

        $this->assertTrue($partner->partnerProfile->fresh()->online_payment);
    }

    public function test_the_payment_url_must_be_a_web_address(): void
    {
        $this->actingAsOfflinePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->set('paymentUrl', 'non è un link')
            ->call('savePaymentMode')
            ->assertHasErrors('paymentUrl')
            ->assertSee('Inserisci un indirizzo web valido.');
    }

    public function test_a_partner_without_profile_gets_one_when_saving_the_mode(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->set('paymentMode', 'on_site')
            ->call('savePaymentMode')
            ->assertHasNoErrors();

        $this->assertFalse($partner->partnerProfile()->firstOrFail()->online_payment);
    }

    public function test_the_stripe_box_speaks_to_an_offline_partner(): void
    {
        $this->actingAsOfflinePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->assertSee(__('partner.profile.stripe.help_on_site'))
            ->assertDontSee(__('partner.profile.stripe.help'))
            // Può preparare Stripe in anticipo per passare online.
            ->assertSee(__('partner.profile.stripe.connect'));
    }
}
