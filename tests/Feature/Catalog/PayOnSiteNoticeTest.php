<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Livewire\Catalog\EventDetail;
use App\Livewire\Catalog\SmartboxDetail;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Un partner che non usa il pagamento online non prende ordini dal sito: la
 * sua scheda si consulta e lo si contatta.
 *
 * Fino al 29/09/2026 la modalità cambiava solo le diciture — il cliente
 * aggiungeva al carrello e finiva comunque al checkout. La cliente ha chiesto
 * di togliere quel passaggio: al posto del box prenotazione ci sono i recapiti
 * del partner.
 *
 * Gli eventi e le attività GRATUITE non c'entrano: la loro CTA è "Partecipa",
 * non passa dal carrello e resta com'era.
 */
class PayOnSiteNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);
    }

    private function offlineOwner(): User
    {
        return User::factory()->offlinePartner()->create();
    }

    private function onlineOwner(): User
    {
        return User::factory()->stripeConnected()->create();
    }

    public function test_la_struttura_di_un_partner_offline_mostra_i_contatti_e_non_il_carrello(): void
    {
        Structure::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'hotel-offline']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-offline'])
            ->assertOk()
            ->assertSee(__('catalog.contacts.title'))
            ->assertSee(__('catalog.contacts.intro'))
            // Niente CTA carrello, né nella card desktop né nella barra mobile.
            ->assertDontSee(__('holiday.add_to_cart'))
            // Niente preventivo: non si quota ciò che non si compra da qui.
            ->assertDontSee(__('holiday.total'));
    }

    public function test_la_struttura_di_un_partner_online_tiene_il_box_prenotazione(): void
    {
        Structure::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'hotel-online']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-online'])
            ->assertOk()
            ->assertSee(__('holiday.add_to_cart'))
            ->assertDontSee(__('catalog.contacts.title'));
    }

    public function test_un_proprietario_senza_profilo_partner_resta_online(): void
    {
        Structure::factory()->create(['slug' => 'hotel-senza-profilo']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-senza-profilo'])
            ->assertOk()
            ->assertSee(__('holiday.add_to_cart'))
            ->assertDontSee(__('catalog.contacts.title'));
    }

    public function test_la_card_contatti_mostra_ragione_sociale_indirizzo_e_sito(): void
    {
        $owner = $this->offlineOwner();
        $owner->partnerProfile->update([
            'business_name' => 'Rifugio delle Alpi srl',
            'address' => 'Via Roma 10',
            'zip' => '25047',
            'city' => 'Darfo',
            'province' => 'BS',
            'payment_url' => 'https://rifugiodellealpi.it/prenota',
        ]);
        Structure::factory()->create(['user_id' => $owner->id, 'slug' => 'rifugio-alpi']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'rifugio-alpi'])
            ->assertOk()
            ->assertSee('Rifugio delle Alpi srl')
            ->assertSee('Via Roma 10, 25047 Darfo (BS)')
            ->assertSee('https://rifugiodellealpi.it/prenota')
            ->assertSee(__('checkout.on_site.pay_on_website'));
    }

    public function test_la_card_contatti_non_stampa_un_link_con_schema_pericoloso(): void
    {
        // payment_url finisce in un href: l'escape di Blade non ferma javascript:.
        $owner = $this->offlineOwner();
        $owner->partnerProfile->update(['payment_url' => 'javascript:alert(1)']);
        Structure::factory()->create(['user_id' => $owner->id, 'slug' => 'hotel-link-storto']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-link-storto'])
            ->assertOk()
            ->assertDontSee('javascript:alert(1)')
            ->assertDontSee(__('checkout.on_site.pay_on_website'));
    }

    public function test_il_servizio_di_un_partner_offline_mostra_i_contatti(): void
    {
        Structure::factory()->service()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'dog-sitting-offline']);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-offline'])
            ->assertOk()
            ->assertSee(__('catalog.contacts.title'))
            ->assertDontSee(__('holiday.add_to_cart'));
    }

    public function test_il_servizio_di_un_partner_online_tiene_il_box_prenotazione(): void
    {
        Structure::factory()->service()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'dog-sitting-online']);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-online'])
            ->assertOk()
            ->assertSee(__('holiday.add_to_cart'))
            ->assertDontSee(__('catalog.contacts.title'));
    }

    public function test_l_attivita_a_pagamento_di_un_partner_offline_mostra_i_contatti(): void
    {
        Event::factory()->activity(3)->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'weekend-offline']);

        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-offline'])
            ->assertOk()
            ->assertSee(__('catalog.contacts.title'))
            ->assertDontSee(__('events.add_to_cart'));
    }

    public function test_l_attivita_gratuita_di_un_partner_offline_resta_partecipa(): void
    {
        Event::factory()->activity(3)->free()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'weekend-gratis']);

        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-gratis'])
            ->assertOk()
            ->assertSee(__('events.join'))
            ->assertDontSee(__('catalog.contacts.title'));
    }

    public function test_l_attivita_di_un_partner_online_tiene_il_carrello(): void
    {
        Event::factory()->activity(3)->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'weekend-online']);

        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-online'])
            ->assertOk()
            ->assertSee(__('events.add_to_cart'))
            ->assertDontSee(__('catalog.contacts.title'));
    }

    public function test_l_evento_a_pagamento_di_un_partner_offline_mostra_i_contatti(): void
    {
        Event::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'brunch-offline']);

        Livewire::test(EventDetail::class, ['event' => 'brunch-offline'])
            ->assertOk()
            ->assertSee(__('catalog.contacts.title'))
            ->assertDontSee(__('events.add_to_cart'));
    }

    public function test_l_evento_gratuito_di_un_partner_offline_resta_partecipa(): void
    {
        Event::factory()->free()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'festa-gratis']);

        Livewire::test(EventDetail::class, ['event' => 'festa-gratis'])
            ->assertOk()
            ->assertSee(__('events.join'))
            ->assertDontSee(__('catalog.contacts.title'));
    }

    public function test_l_evento_di_un_partner_online_tiene_il_carrello(): void
    {
        Event::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'brunch-online']);

        Livewire::test(EventDetail::class, ['event' => 'brunch-online'])
            ->assertOk()
            ->assertSee(__('events.add_to_cart'))
            ->assertDontSee(__('catalog.contacts.title'));
    }

    public function test_lo_smartbox_di_un_partner_offline_mostra_i_contatti(): void
    {
        SmartboxPackage::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'relax-offline']);

        Livewire::test(SmartboxDetail::class, ['box' => 'relax-offline'])
            ->assertOk()
            ->assertSee(__('catalog.contacts.title'))
            ->assertDontSee(__('smartbox.add_to_cart'));
    }

    public function test_lo_smartbox_di_un_partner_online_tiene_il_carrello(): void
    {
        SmartboxPackage::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'relax-online']);

        Livewire::test(SmartboxDetail::class, ['box' => 'relax-online'])
            ->assertOk()
            ->assertSee(__('smartbox.add_to_cart'))
            ->assertDontSee(__('catalog.contacts.title'));
    }
}
