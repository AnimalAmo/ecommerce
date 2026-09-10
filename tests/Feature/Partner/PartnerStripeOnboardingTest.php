<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfilePayment;
use App\Models\Partner\PartnerProfile;
use App\Services\Payment\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Il partner collega il proprio conto Stripe dalla pagina "Metodo di
 * pagamento": è il gesto senza il quale non può pubblicare nulla, quindi la
 * pagina deve dire a che punto è l'onboarding, non solo offrire un pulsante.
 */
class PartnerStripeOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_partner_non_collegato_vede_l_invito_a_collegarsi(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        Livewire::test(PartnerProfilePayment::class)
            ->assertSee(__('partner.profile.stripe.disconnected'))
            ->assertSee(__('partner.profile.stripe.connect'));
    }

    public function test_un_onboarding_incompleto_elenca_cosa_manca(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create([
            'stripe_account_id' => 'acct_x',
            'stripe_charges_enabled' => true,
            'stripe_payouts_enabled' => false,
            'stripe_requirements_due' => ['external_account'],
        ]);

        Livewire::test(PartnerProfilePayment::class)
            ->assertSee(__('partner.profile.stripe.incomplete'))
            ->assertSee('external_account')
            ->assertSee(__('partner.profile.stripe.resume'));
    }

    public function test_un_partner_collegato_vede_lo_stato_completo(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->connected()->for($partner)->create();

        Livewire::test(PartnerProfilePayment::class)
            ->assertSee(__('partner.profile.stripe.connected'))
            ->assertDontSee(__('partner.profile.stripe.connect'));
    }

    public function test_il_pulsante_porta_al_link_di_onboarding_di_stripe(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        $this->mock(StripeConnectService::class, function (MockInterface $mock) use ($partner): void {
            $mock->shouldReceive('onboardingUrl')
                ->once()
                ->with(
                    \Mockery::on(fn ($user): bool => $user->is($partner)),
                    route('partner.profile.payment'),
                    route('partner.profile.payment'),
                )
                ->andReturn('https://connect.stripe.com/setup/x');
        });

        Livewire::test(PartnerProfilePayment::class)
            ->call('connectStripe')
            ->assertRedirect('https://connect.stripe.com/setup/x');
    }
}
