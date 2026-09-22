<?php

namespace Tests\Feature\Partner;

use App\Enums\OrderPaymentMode;
use App\Exceptions\PaymentModeException;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regole del cambio di modalità (22/09/2026): passare a offline è sempre
 * permesso, tornare online richiede Stripe operativo. Senza profilo si
 * resta al comportamento di prima, cioè online.
 */
class PartnerPaymentModeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    private function modes(): PartnerPaymentModeService
    {
        return app(PartnerPaymentModeService::class);
    }

    public function test_un_partner_online_passa_in_struttura_senza_stripe(): void
    {
        $profile = PartnerProfile::factory()->create();

        $saved = $this->modes()->set($profile, false, 'https://www.hotelrosovino.it');

        $this->assertSame($profile->id, $saved->id);
        $this->assertDatabaseHas('partner_profiles', [
            'id' => $profile->id,
            'online_payment' => false,
            'payment_url' => 'https://www.hotelrosovino.it',
        ]);
    }

    public function test_un_partner_offline_senza_stripe_non_torna_online(): void
    {
        $profile = PartnerProfile::factory()->offline()->create();

        try {
            $this->modes()->set($profile, true, null);
            $this->fail('Attesa PaymentModeException per il passaggio a online senza Stripe.');
        } catch (PaymentModeException $exception) {
            $this->assertSame(__('partner.payment_mode.errors.stripe_required'), $exception->getMessage());
        }

        $this->assertFalse($profile->fresh()->online_payment);
    }

    public function test_con_onboarding_a_meta_non_si_torna_online(): void
    {
        // charges ma non payouts: incasserebbe senza poter essere bonificato.
        $profile = PartnerProfile::factory()->connected()->create([
            'online_payment' => false,
            'stripe_payouts_enabled' => false,
        ]);

        $this->expectException(PaymentModeException::class);

        $this->modes()->set($profile, true, null);
    }

    public function test_un_partner_offline_con_stripe_torna_online(): void
    {
        $profile = PartnerProfile::factory()->connected()->create(['online_payment' => false]);

        $this->modes()->set($profile, true, null);

        $this->assertTrue($profile->fresh()->online_payment);
    }

    public function test_restare_online_senza_stripe_non_e_un_passaggio(): void
    {
        // Partner appena iscritto, o creato dall'admin: è già online, e salvare
        // il link non deve pretendere Stripe.
        $profile = PartnerProfile::factory()->create();

        $this->modes()->set($profile, true, 'https://www.hotelrosovino.it');

        $fresh = $profile->fresh();
        $this->assertTrue($fresh->online_payment);
        $this->assertSame('https://www.hotelrosovino.it', $fresh->payment_url);
    }

    public function test_il_link_vuoto_diventa_null_e_quello_pieno_si_ripulisce(): void
    {
        $profile = PartnerProfile::factory()->create(['payment_url' => 'https://vecchio.it']);

        $this->modes()->set($profile, false, '   ');
        $this->assertNull($profile->fresh()->payment_url);

        $this->modes()->set($profile, false, '  https://www.hotelrosovino.it  ');
        $this->assertSame('https://www.hotelrosovino.it', $profile->fresh()->payment_url);
    }

    public function test_senza_profilo_la_modalita_e_online(): void
    {
        $client = User::factory()->create();

        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forOwner(null));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forOwner($client->id));
        $this->assertNull($this->modes()->profileFor($client->id));
        $this->assertNull($this->modes()->profileFor(null));
    }

    public function test_for_owner_legge_la_modalita_del_partner(): void
    {
        $offline = User::factory()->offlinePartner()->create();
        $online = User::factory()->stripeConnected()->create();

        $this->assertSame(OrderPaymentMode::OnSite, $this->modes()->forOwner($offline->id));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forOwner($online->id));
        $this->assertTrue($this->modes()->profileFor($offline->id)->is($offline->partnerProfile));
    }

    public function test_for_purchasable_usa_il_titolare_del_prodotto(): void
    {
        $offline = User::factory()->offlinePartner()->create();
        $structure = Structure::factory()->create(['user_id' => $offline->id]);
        $orphan = Structure::factory()->create(['user_id' => null]);

        $this->assertSame(OrderPaymentMode::OnSite, $this->modes()->forPurchasable($structure));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forPurchasable($orphan));
        $this->assertSame(OrderPaymentMode::Online, $this->modes()->forPurchasable(null));
    }

    public function test_la_modalita_si_legge_una_volta_per_richiesta(): void
    {
        $offline = User::factory()->offlinePartner()->create();
        $client = User::factory()->create();
        $modes = $this->modes();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $modes->forOwner($offline->id);
        $modes->forOwner($offline->id);
        $modes->profileFor($offline->id);
        // Anche l'assenza del profilo si ricorda.
        $modes->forOwner($client->id);
        $modes->forOwner($client->id);

        $this->assertCount(2, DB::getQueryLog());
    }

    public function test_dopo_il_salvataggio_la_memoria_e_aggiornata(): void
    {
        $partner = User::factory()->create();
        $profile = PartnerProfile::factory()->for($partner)->create();
        $modes = $this->modes();

        $this->assertSame(OrderPaymentMode::Online, $modes->forOwner($partner->id));

        $modes->set($profile, false, null);

        $this->assertSame(OrderPaymentMode::OnSite, $modes->forOwner($partner->id));
    }

    public function test_il_service_vive_quanto_una_richiesta(): void
    {
        $this->assertSame(app(PartnerPaymentModeService::class), app(PartnerPaymentModeService::class));

        $before = app(PartnerPaymentModeService::class);
        app()->forgetScopedInstances();

        $this->assertNotSame($before, app(PartnerPaymentModeService::class));
    }
}
