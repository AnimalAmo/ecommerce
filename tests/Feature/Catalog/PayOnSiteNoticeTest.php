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
 * Chi prenota da un partner senza pagamento online deve saperlo prima del
 * carrello: la dicitura sta accanto al totale di ogni scheda dettaglio.
 * Sulle card delle liste no: costerebbe una query in più per card.
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

    public function test_la_struttura_di_un_partner_offline_mostra_la_dicitura_nella_card_e_nella_barra_mobile(): void
    {
        Structure::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'hotel-offline']);

        $html = Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-offline'])
            ->assertOk()
            ->assertSee(__('catalog.pay_on_site'))
            ->html();

        // Card desktop (nascosta su mobile) + barra CTA fissa mobile.
        $this->assertSame(2, substr_count($html, e(__('catalog.pay_on_site'))));
    }

    public function test_la_struttura_di_un_partner_online_non_mostra_la_dicitura(): void
    {
        Structure::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'hotel-online']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-online'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }

    public function test_un_proprietario_senza_profilo_partner_resta_online(): void
    {
        Structure::factory()->create(['slug' => 'hotel-senza-profilo']);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-senza-profilo'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }

    public function test_il_servizio_di_un_partner_offline_mostra_la_dicitura(): void
    {
        Structure::factory()->service()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'dog-sitting-offline']);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-offline'])
            ->assertOk()
            ->assertSee(__('catalog.pay_on_site'));
    }

    public function test_il_servizio_di_un_partner_online_non_mostra_la_dicitura(): void
    {
        Structure::factory()->service()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'dog-sitting-online']);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-online'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }

    public function test_l_attivita_a_pagamento_di_un_partner_offline_mostra_la_dicitura(): void
    {
        Event::factory()->activity(3)->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'weekend-offline']);

        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-offline'])
            ->assertOk()
            ->assertSee(__('catalog.pay_on_site'));
    }

    public function test_l_attivita_gratuita_di_un_partner_offline_resta_partecipa_senza_dicitura(): void
    {
        Event::factory()->activity(3)->free()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'weekend-gratis']);

        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-gratis'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }

    public function test_l_attivita_di_un_partner_online_non_mostra_la_dicitura(): void
    {
        Event::factory()->activity(3)->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'weekend-online']);

        Livewire::test(ActivityDetail::class, ['activity' => 'weekend-online'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }

    public function test_l_evento_a_pagamento_di_un_partner_offline_mostra_la_dicitura(): void
    {
        Event::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'brunch-offline']);

        Livewire::test(EventDetail::class, ['event' => 'brunch-offline'])
            ->assertOk()
            ->assertSee(__('catalog.pay_on_site'));
    }

    public function test_l_evento_gratuito_di_un_partner_offline_non_mostra_la_dicitura(): void
    {
        Event::factory()->free()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'festa-gratis']);

        Livewire::test(EventDetail::class, ['event' => 'festa-gratis'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }

    public function test_l_evento_di_un_partner_online_non_mostra_la_dicitura(): void
    {
        Event::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'brunch-online']);

        Livewire::test(EventDetail::class, ['event' => 'brunch-online'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }

    public function test_lo_smartbox_di_un_partner_offline_mostra_la_dicitura(): void
    {
        SmartboxPackage::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'relax-offline']);

        Livewire::test(SmartboxDetail::class, ['box' => 'relax-offline'])
            ->assertOk()
            ->assertSee(__('catalog.pay_on_site'));
    }

    public function test_lo_smartbox_di_un_partner_online_non_mostra_la_dicitura(): void
    {
        SmartboxPackage::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'relax-online']);

        Livewire::test(SmartboxDetail::class, ['box' => 'relax-online'])
            ->assertOk()
            ->assertDontSee(__('catalog.pay_on_site'));
    }
}
