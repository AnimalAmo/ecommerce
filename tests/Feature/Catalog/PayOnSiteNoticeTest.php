<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\ActivityDetail;
use App\Livewire\Catalog\AnimalHolidayRegion;
use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Livewire\Catalog\EventDetail;
use App\Livewire\Catalog\Events;
use App\Livewire\Catalog\SmartboxDetail;
use App\Models\Event\Event;
use App\Models\Region\Region;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    // ── Le stesse regole nelle LISTE (27/09/2026) ───────────────────────────
    //
    // Fino a ieri la regola valeva solo sulle schede di dettaglio: nelle griglie
    // il pulsante carrello c'era per tutti e portava a un checkout che si
    // blocca. Un pulsante che non funziona è peggio di nessun pulsante.

    public function test_la_griglia_eventi_rimanda_alla_scheda_per_un_titolare_offline(): void
    {
        Event::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'brunch-in-griglia']);

        Livewire::test(Events::class)
            ->assertOk()
            ->assertSee(__('catalog.book_with_partner'))
            // Il pulsante carrello non c'è: si cerca la sua azione, non la sua
            // etichetta, perché è l'azione che porterebbe al checkout bloccato.
            ->assertDontSeeHtml('wire:click="addToCart(');
    }

    public function test_la_griglia_eventi_tiene_il_carrello_per_un_titolare_online(): void
    {
        Event::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'brunch-online-in-griglia']);

        Livewire::test(Events::class)
            ->assertOk()
            ->assertSee(__('events.add_to_cart'))
            ->assertSeeHtml('wire:click="addToCart(')
            ->assertDontSee(__('catalog.book_with_partner'));
    }

    /**
     * Il costo della regola in griglia: UNA query, non una per card. È la ragione
     * per cui `PartnerPaymentModeService::forOwners()` esiste, quindi va tenuta
     * ferma qui, dove la N+1 nascerebbe.
     *
     * Si contano solo le query su `partner_profiles` e non tutte quelle della
     * pagina: il totale cambierebbe a ogni ritocco della griglia e questo test
     * diventerebbe un test di quante query fa il catalogo, che non è il suo tema.
     */
    public function test_la_griglia_eventi_legge_le_modalita_in_una_query_sola(): void
    {
        foreach (range(1, 3) as $index) {
            Event::factory()->create([
                'user_id' => $this->offlineOwner()->id,
                'slug' => 'evento-offline-'.$index,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(Events::class)->assertOk();

        $profileQueries = collect(DB::getQueryLog())
            ->filter(fn (array $entry): bool => str_contains($entry['query'], 'partner_profiles'));

        $this->assertCount(
            1,
            $profileQueries,
            'Tre titolari diversi in griglia devono costare una query sola: '.$profileQueries->count().' significa N+1.',
        );
    }

    public function test_la_pagina_regione_rimanda_alla_scheda_per_un_titolare_offline(): void
    {
        Event::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'brunch-in-regione']);

        // Le chip della pagina regione partono su hotel/servizi: senza accendere
        // "eventi" la griglia eventi non viene nemmeno interrogata.
        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['eventi'])
            ->assertOk()
            ->assertSee(__('catalog.book_with_partner'))
            ->assertDontSeeHtml('wire:click="addToCart(');
    }

    public function test_la_pagina_regione_tiene_il_carrello_per_un_titolare_online(): void
    {
        Event::factory()->create(['user_id' => $this->onlineOwner()->id, 'slug' => 'brunch-online-in-regione']);

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['eventi'])
            ->assertOk()
            ->assertSeeHtml('wire:click="addToCart(')
            ->assertDontSee(__('catalog.book_with_partner'));
    }

    /**
     * Il pulsante non c'è più, ma `addToCart($id)` arriva dal payload del client:
     * senza la guardia server-side la riga entrerebbe in un carrello che al
     * checkout si blocca.
     */
    public function test_l_aggiunta_rapida_di_un_evento_di_un_titolare_offline_e_rifiutata(): void
    {
        $event = Event::factory()->create(['user_id' => $this->offlineOwner()->id, 'slug' => 'brunch-a-mano']);

        Livewire::test(Events::class)
            ->call('addToCart', $event->id)
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('cart.not_purchasable'))
            ->assertNotDispatched('cart-updated');

        $this->assertTrue(app(CartManager::class)->items()->isEmpty());
    }

    // ── Difetto C5: nelle liste la borsa ignora la capienza ──────────────────
    //
    // Le tre liste guardano solo `hasJoinCta()` e la modalità di incasso del
    // titolare; `max_participants`/`booked_participants` non entrano in nessuno
    // dei tre rami. La scheda di dettaglio si comporta bene, quindi lo stesso
    // evento offre la borsa in griglia e la nega aprendolo — e il click dà solo
    // il toast di AvailabilityService. Stessa regola già scritta qui sopra per la
    // modalità di incasso: una borsa che non funziona è peggio di nessuna borsa.

    /** Evento a pagamento, di un titolare online, a posti esauriti. */
    private function soldOutEvent(string $slug, string $title): Event
    {
        return Event::factory()->create([
            'user_id' => $this->onlineOwner()->id,
            'slug' => $slug,
            'title' => $title,
            'max_participants' => 10,
            'booked_participants' => 10,
        ]);
    }

    public function test_la_griglia_eventi_non_offre_il_carrello_per_un_evento_pieno(): void
    {
        $this->soldOutEvent('evento-pieno-in-griglia', 'Evento al completo');

        Livewire::test(Events::class)
            ->assertOk()
            ->assertSee('Evento al completo')
            ->assertDontSeeHtml('wire:click="addToCart(')
            // E la lista deve dirlo: oggi non dice niente.
            ->assertSee(__('cart.sold_out'));
    }

    public function test_la_pagina_regione_non_offre_il_carrello_per_un_evento_pieno(): void
    {
        $this->soldOutEvent('evento-pieno-in-regione', 'Evento pieno in regione');

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['eventi'])
            ->assertOk()
            ->assertSee('Evento pieno in regione')
            ->assertDontSeeHtml('wire:click="addToCart(')
            ->assertSee(__('cart.sold_out'));
    }

    /**
     * `FavoriteService::canAddToCart()` è nato per questa regola e guarda
     * `hasJoinCta()` e la modalità del titolare, non la capienza: la borsa di
     * partials/favorite-card.blade.php è gated solo su `can_add_to_cart`.
     */
    public function test_i_preferiti_non_offrono_la_borsa_per_un_evento_pieno(): void
    {
        $event = $this->soldOutEvent('evento-pieno-nei-preferiti', 'Evento pieno nei preferiti');
        $user = User::factory()->create();
        $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $event->id]);

        $this->actingAs($user)->get(route('preferiti'))
            ->assertOk()
            ->assertSee('Evento pieno nei preferiti')
            ->assertDontSee(__('nav.card.add_to_cart'));
    }

    /** Il negativo: con un posto libero la borsa resta, in tutte e tre. */
    public function test_con_un_posto_libero_la_borsa_resta_nelle_liste(): void
    {
        $event = Event::factory()->create([
            'user_id' => $this->onlineOwner()->id,
            'slug' => 'evento-quasi-pieno',
            'title' => 'Evento quasi pieno',
            'max_participants' => 10,
            'booked_participants' => 9,
        ]);

        Livewire::test(Events::class)
            ->assertOk()
            ->assertSeeHtml('wire:click="addToCart(')
            ->assertDontSee(__('cart.sold_out'));

        $user = User::factory()->create();
        $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $event->id]);

        $this->actingAs($user)->get(route('preferiti'))
            ->assertOk()
            ->assertSee(__('nav.card.add_to_cart'));
    }
}
