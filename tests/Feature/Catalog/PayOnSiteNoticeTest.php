<?php

namespace Tests\Feature\Catalog;

use App\Exceptions\CartValidationException;
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
use App\Services\FavoriteService;
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
     * La borsa di partials/favorite-card.blade.php è gated solo su
     * `can_add_to_cart`, e `FavoriteService::canAddToCart()` guardava
     * `hasJoinCta()` e la modalità del titolare ma non la capienza. Dal
     * 28/09/2026 esclude anche i posti esauriti (FavoriteService::isSoldOut()).
     */
    public function test_i_preferiti_non_offrono_la_borsa_per_un_evento_pieno(): void
    {
        $event = $this->soldOutEvent('evento-pieno-nei-preferiti', 'Evento pieno nei preferiti');
        $user = User::factory()->create();
        $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $event->id]);

        $this->actingAs($user)->get(route('preferiti'))
            ->assertOk()
            ->assertSee('Evento pieno nei preferiti')
            ->assertDontSee(__('nav.card.add_to_cart'))
            // Tolta la borsa, la card dice perché (partial favorite-card, `sold_out`).
            ->assertSee(__('cart.sold_out'));
    }

    /**
     * Le card «più amate» dello stato vuoto del carrello usano lo stesso partial
     * ma passano da `topFavorited()`, un'altra strada per `can_add_to_cart` e
     * `sold_out`: senza questa prova una delle due potrebbe perdere la regola.
     */
    public function test_le_card_piu_amate_del_carrello_vuoto_dicono_che_un_evento_e_pieno(): void
    {
        $event = $this->soldOutEvent('evento-pieno-piu-amato', 'Evento pieno più amato');
        User::factory()->create()->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $event->id]);

        $this->get(route('carrello'))
            ->assertOk()
            ->assertSee('Evento pieno più amato')
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('nav.card.add_to_cart'))
            ->assertDontSeeHtml('toggleSuggestionCart(');
    }

    /**
     * Un evento GRATUITO pieno: la sua CTA è «Partecipa», che non passa dal
     * carrello, ma prometterebbe un posto che non c'è. Nella scheda il pieno
     * vince su «Partecipa»; nelle liste deve valere lo stesso ordine.
     */
    public function test_un_evento_gratuito_pieno_nelle_liste_non_offre_partecipa(): void
    {
        Event::factory()->free()->create([
            'user_id' => $this->onlineOwner()->id,
            'slug' => 'gratis-ma-pieno',
            'title' => 'Gratis ma pieno',
            'max_participants' => 5,
            'booked_participants' => 5,
        ]);

        Livewire::test(Events::class)
            ->assertOk()
            ->assertSee('Gratis ma pieno')
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('events.join'));

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['eventi'])
            ->assertOk()
            ->assertSee('Gratis ma pieno')
            ->assertSee(__('cart.sold_out'))
            ->assertDontSee(__('events.join'));
    }

    /** Il negativo del precedente: con posti liberi l'evento gratuito resta «Partecipa». */
    public function test_un_evento_gratuito_con_posti_resta_partecipa_nelle_liste(): void
    {
        Event::factory()->free()->create([
            'user_id' => $this->onlineOwner()->id,
            'slug' => 'gratis-con-posti',
            'title' => 'Gratis con posti',
            'max_participants' => 5,
            'booked_participants' => 4,
        ]);

        Livewire::test(Events::class)
            ->assertSee(__('events.join'))
            ->assertDontSee(__('cart.sold_out'));

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->set('activeTypes', ['eventi'])
            ->assertSee(__('events.join'))
            ->assertDontSee(__('cart.sold_out'));
    }

    /**
     * La regola del pieno legge colonne delle righe già caricate: tre eventi
     * pieni devono costare esattamente le query di tre eventi con posti. Si
     * confronta la stessa pagina con gli stessi eventi, cambiando solo i posti
     * venduti, così il numero assoluto di query del catalogo non entra nella prova.
     */
    public function test_il_pieno_nelle_liste_non_costa_query(): void
    {
        $events = collect(range(1, 3))->map(fn (int $index): Event => Event::factory()->create([
            'user_id' => $this->onlineOwner()->id,
            'slug' => 'evento-contato-'.$index,
            'max_participants' => 10,
            'booked_participants' => 0,
        ]));

        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();

            Livewire::test(Events::class)->assertOk();
            Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])->set('activeTypes', ['eventi'])->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        // Primo giro a vuoto: cache e letture una tantum (permessi, config)
        // non devono finire in uno solo dei due conteggi.
        $countQueries();
        $withSeats = $countQueries();

        $events->each(fn (Event $event) => $event->update(['booked_participants' => 10]));

        // Fixture: ora la griglia disegna davvero il ramo del pieno.
        Livewire::test(Events::class)->assertSee(__('cart.sold_out'))->assertDontSeeHtml('wire:click="addToCart(');

        $this->assertSame($withSeats, $countQueries(), 'Il ramo «esaurito» delle liste non deve aggiungere query.');

        // Il confronto sopra non vede una query PER RIGA dentro la regola (review
        // del 28/09/2026): crescerebbe allo stesso modo nei due conteggi. Qui la
        // si misura da sola, sulle righe già caricate: zero query, in entrambe
        // le soglie.
        $loaded = Event::query()->whereKey($events->pluck('id')->all())->get();
        $favorites = app(FavoriteService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $loaded->each(function (Event $event) use ($favorites): void {
            $favorites->isSoldOut($event);
            $favorites->isSoldOut($event, ownerOnSite: true);
        });
        $ruleQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $ruleQueries, 'La regola del pieno deve leggere solo le colonne già caricate.');
    }

    /*
     * Il rovescio di C5, cioè C2 nelle liste (trovato dal tester il 28/09/2026,
     * chiuso lo stesso giorno). La soglia delle liste era «un posto per una
     * persona», ma la borsa di un'ATTIVITÀ non aggiunge una persona:
     * AddsEventToCart e FavoriteService::defaultCartOptions() mettono due
     * adulti, e AvailabilityService rifiuta con `booked + 2 > max`. Con un
     * posto libero su dieci la borsa era disegnata e rispondeva solo col toast
     * «Non ci sono abbastanza posti disponibili». Ora soglia e aggiunta rapida
     * leggono la stessa regola, Event::quickAddPersons().
     *
     * Le due prove col posto solo non dicono QUALE cura: dicono che una borsa
     * mostrata deve funzionare. Da sole passerebbero anche con una griglia che
     * non offre mai la borsa, per questo c'è la terza, col caso positivo.
     */

    /** Attività a pagamento, titolare online, un solo posto libero su dieci. */
    private function activityWithOneSeatLeft(string $slug): Event
    {
        return Event::factory()->activity()->create([
            'user_id' => $this->onlineOwner()->id,
            'slug' => $slug,
            'title' => 'Attività con un posto',
            'max_participants' => 10,
            'booked_participants' => 9,
        ]);
    }

    public function test_la_griglia_non_offre_una_borsa_che_rifiuta_l_attivita_con_un_posto_solo(): void
    {
        $activity = $this->activityWithOneSeatLeft('attivita-un-posto-griglia');

        $grid = Livewire::test(Events::class)->assertSee('Attività con un posto');
        $offered = str_contains($grid->html(), 'wire:click="addToCart('.$activity->id.')"');

        $grid->call('addToCart', $activity->id);
        $added = app(CartManager::class)->items()->count() === 1;

        $this->assertTrue(
            ! $offered || $added,
            'La griglia disegna «Aggiungi al carrello» per un\'attività con un posto libero, ma il click '
            .'aggiunge due adulti e AvailabilityService lo rifiuta: un pulsante che risponde solo con un errore.',
        );
    }

    /**
     * Il caso positivo delle due prove sopra: con due posti liberi la borsa di
     * un'attività c'è, e il click mette davvero in carrello i due adulti.
     */
    public function test_con_due_posti_liberi_la_borsa_di_unattivita_resta_e_aggiunge(): void
    {
        $activity = Event::factory()->activity()->create([
            'user_id' => $this->onlineOwner()->id,
            'slug' => 'attivita-due-posti-griglia',
            'title' => 'Attività con due posti',
            'max_participants' => 10,
            'booked_participants' => 8,
        ]);

        $grid = Livewire::test(Events::class)->assertSee('Attività con due posti');

        $this->assertStringContainsString('wire:click="addToCart('.$activity->id.')"', $grid->html());

        $grid->call('addToCart', $activity->id);

        $this->assertSame(1, app(CartManager::class)->items()->count());
    }

    /**
     * Il limite della soglia dei due adulti (review del 28/09/2026): vale solo
     * dove la CTA della lista aggiunge davvero al carrello. Per un titolare
     * che incassa in struttura la CTA è «Prenota con il partner», un rimando
     * alla scheda, e la scheda con un posto libero offre i contatti: la lista
     * non può dire «esaurito» sullo stesso dato.
     */
    public function test_con_un_posto_libero_chi_incassa_in_struttura_resta_prenotabile_dalla_lista(): void
    {
        Event::factory()->activity()->create([
            'user_id' => $this->offlineOwner()->id,
            'slug' => 'attivita-un-posto-in-struttura',
            'title' => 'Attività in struttura con un posto',
            'max_participants' => 10,
            'booked_participants' => 9,
        ]);

        Livewire::test(Events::class)
            ->assertSee('Attività in struttura con un posto')
            ->assertSee(__('catalog.book_with_partner'))
            ->assertDontSee(__('cart.sold_out'));
    }

    public function test_i_preferiti_non_offrono_una_borsa_che_rifiuta_l_attivita_con_un_posto_solo(): void
    {
        $activity = $this->activityWithOneSeatLeft('attivita-un-posto-preferiti');
        $user = User::factory()->create();
        $favorite = $user->favorites()->create(['favoritable_type' => 'event', 'favoritable_id' => $activity->id]);
        $this->actingAs($user);

        $offered = str_contains(
            $this->get(route('preferiti'))->assertOk()->getContent(),
            e(__('nav.card.add_to_cart')),
        );

        try {
            app(FavoriteService::class)->addToCart($user, $favorite->id);
        } catch (CartValidationException) {
            // Il rifiuto è proprio il sintomo: lo misura il conteggio qui sotto.
        }

        $added = app(CartManager::class)->items()->count() === 1;

        $this->assertTrue(
            ! $offered || $added,
            'La card del preferito mostra la borsa per un\'attività con un posto libero, ma la borsa '
            .'aggiunge due adulti e il carrello la rifiuta con un toast.',
        );
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
