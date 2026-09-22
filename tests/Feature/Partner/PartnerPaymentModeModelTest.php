<?php

namespace Tests\Feature\Partner;

use App\Enums\OrderPaymentMode;
use App\Enums\OrderStatus;
use App\Models\Order\Order;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pagamento online facoltativo (richiesta della cliente, 22/09/2026): il
 * partner sceglie se farsi pagare online su AnimalAmo o direttamente, in
 * struttura o sul suo sito. I partner di prima restano online, e ogni ordine
 * ricorda la modalità con cui è nato.
 */
class PartnerPaymentModeModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    public function test_un_profilo_nuovo_nasce_a_pagamento_online(): void
    {
        $user = User::factory()->create();
        $profile = $user->partnerProfile()->create(['business_name' => 'Hotel Rosovino']);

        // In memoria la colonna manca (la scrive il default del database): conta come online.
        $this->assertTrue($profile->requiresOnlinePayment());

        $fresh = $profile->fresh();
        $this->assertTrue($fresh->online_payment);
        $this->assertNull($fresh->payment_url);
        $this->assertSame(OrderPaymentMode::Online, $fresh->paymentMode());
    }

    public function test_un_partner_offline_non_chiede_il_pagamento_online(): void
    {
        $profile = PartnerProfile::factory()->offline()->make(['user_id' => null]);

        $this->assertFalse($profile->requiresOnlinePayment());
        $this->assertSame(OrderPaymentMode::OnSite, $profile->paymentMode());
        $this->assertFalse($profile->canSell());
    }

    public function test_can_publish_nelle_quattro_combinazioni(): void
    {
        $onlinePayable = PartnerProfile::factory()->connected()->make(['user_id' => null]);
        $onlineNotPayable = PartnerProfile::factory()->make(['user_id' => null]);
        $offlineNotPayable = PartnerProfile::factory()->offline()->make(['user_id' => null]);
        $offlinePayable = PartnerProfile::factory()->connected()->make(['user_id' => null, 'online_payment' => false]);

        $this->assertTrue($onlinePayable->canPublish());
        $this->assertFalse($onlineNotPayable->canPublish());
        $this->assertTrue($offlineNotPayable->canPublish());
        $this->assertTrue($offlinePayable->canPublish());

        // canBePaid resta una pura verifica Stripe: decide i bonifici degli
        // ordini online passati anche di chi nel frattempo è passato offline.
        $this->assertFalse($offlineNotPayable->canBePaid());
        $this->assertTrue($offlinePayable->canBePaid());
    }

    public function test_l_ordine_in_struttura_legge_la_modalita_come_enum(): void
    {
        $order = Order::factory()->onSite()->create()->fresh();

        $this->assertSame(OrderPaymentMode::OnSite, $order->payment_mode);
        $this->assertTrue($order->isOnSite());
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertSame(26, strlen($order->checkout_token));
    }

    public function test_gli_ordini_di_prima_restano_online(): void
    {
        // Riga scritta senza la colonna, come gli ordini già a database.
        $id = DB::table('orders')->insertGetId([
            'order_number' => 'ORD-LEGACY',
            'status' => 'paid',
            'first_name' => 'Susanna',
            'last_name' => 'Rossi',
            'email' => 'susanna@example.com',
            'total_cents' => 12000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $legacy = Order::findOrFail($id);

        $this->assertSame(OrderPaymentMode::Online, $legacy->payment_mode);
        $this->assertFalse($legacy->isOnSite());
        $this->assertNull($legacy->checkout_token);
        $this->assertFalse(Order::factory()->paid()->create()->fresh()->isOnSite());
    }

    public function test_le_prenotazioni_valide_sono_pagate_o_confermate(): void
    {
        $this->assertSame([OrderStatus::Paid, OrderStatus::Confirmed], OrderStatus::bookingStatuses());
    }

    public function test_etichette_della_modalita_e_dello_stato(): void
    {
        $this->assertSame('Confermato', OrderStatus::Confirmed->label());
        $this->assertSame('Pagamento online', OrderPaymentMode::Online->label());
        $this->assertSame('Pagamento in struttura', OrderPaymentMode::OnSite->label());
        $this->assertSame('Confermato', __('admin-people.users.order_statuses.confirmed'));
    }

    public function test_la_factory_del_partner_offline(): void
    {
        $partner = User::factory()->offlinePartner()->create();

        $this->assertTrue($partner->hasRole('partner'));
        $this->assertTrue($partner->is_active);
        $this->assertFalse($partner->partnerProfile->requiresOnlinePayment());
        $this->assertFalse($partner->partnerProfile->canBePaid());
        $this->assertTrue($partner->partnerProfile->canPublish());
    }

    public function test_acting_as_offline_partner_autentica_un_partner_offline(): void
    {
        $partner = $this->actingAsOfflinePartner();

        $this->assertAuthenticatedAs($partner);
        $this->assertSame(OrderPaymentMode::OnSite, $partner->partnerProfile->paymentMode());
    }

    public function test_il_partner_collegato_resta_online(): void
    {
        // connected() dopo lo stato offline lo riporta online: è il partner "di prima".
        $this->assertTrue(PartnerProfile::factory()->offline()->connected()->make(['user_id' => null])->online_payment);
    }
}
