<?php

namespace Tests\Feature\Admin\People;

use App\Livewire\Admin\People\UserShow;
use App\Mail\PartnerWelcomeMail;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Partner\PartnerApplication;
use App\Models\Partner\PartnerProfile;
use App\Models\Pet\Pet;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\People\UserDirectory;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Il pannello è solo italiano e `lang/en/admin-people.php` non esiste:
     * sotto APP_LOCALE=en ogni __('admin-people.*') tornerebbe la chiave sia
     * nella vista sia nell'attesa, e gli assert passerebbero a vuoto.
     */
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    public function test_the_profile_shows_orders_pets_and_applications(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create(['first_name' => 'Giulio', 'last_name' => 'Amadori', 'city' => 'Mantova']);
        $order = Order::factory()->paid()->for($user)->create(['total_cents' => 37000]);
        Pet::factory()->for($user)->create(['species' => 'Cane', 'name' => 'Birba']);
        PartnerApplication::create([
            'user_id' => $user->id,
            'first_name' => 'Giulio',
            'last_name' => 'Amadori',
            'email' => $user->email,
            'phone' => '+393331112222',
            'city' => 'Mantova',
            'business_name' => 'Dog Sitter Amadori',
            'role' => 'Titolare',
            'offer_type' => 'Servizi per animali',
            'description' => 'Faccio il dog sitter.',
            'status' => PartnerApplication::STATUS_INVITED,
        ]);

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Giulio Amadori')
            ->assertSee('Mantova')
            ->assertSee($order->order_number)
            ->assertSee('€ 370')
            ->assertSee('Birba')
            ->assertSee('Dog Sitter Amadori')
            ->assertSee('Invitato');
    }

    public function test_each_order_lists_what_was_booked_and_when(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create();
        $order = Order::factory()->paid()->for($user)->create();
        OrderItem::factory()->for($order)->create([
            'title' => 'Hotel Brescia',
            'booked_from' => '2026-10-12 00:00:00',
            'booked_until' => '2026-10-14 00:00:00',
        ]);
        OrderItem::factory()->forSmartbox()->for($order)->create(['title' => 'Due notti sul Garda']);

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Prenotazioni')
            ->assertSee('Hotel Brescia')
            ->assertSee('12/10/2026 – 14/10/2026')
            ->assertSee('Due notti sul Garda');
    }

    public function test_a_partner_profile_shows_listings_and_bookings_received(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('partner', 'web');

        $partner = User::factory()->create();
        $partner->assignRole('partner');
        PartnerProfile::factory()->for($partner)->create(['business_name' => 'Hotel Brescia srl']);

        $live = Structure::factory()->for($partner)->create();
        $suspended = Structure::factory()->for($partner)->create();
        $suspended->forceFill(['suspended_at' => now()])->save();

        // Una prenotazione pagata e una no: conta solo la prima.
        $paid = Order::factory()->paid()->create();
        OrderItem::factory()->for($paid)->create(['purchasable_id' => $live->id, 'partner_user_id' => $partner->id]);
        OrderItem::factory()->for(Order::factory()->create())->create(['purchasable_id' => $live->id, 'partner_user_id' => $partner->id]);

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee('Hotel Brescia srl')
            ->assertSee('2 schede, 1 sospesa')
            ->assertSee('1 prenotazione confermata')
            ->assertSee(route('admin.catalog.index', ['partner' => $partner->id]), escape: false);
    }

    public function test_a_confirmed_on_site_order_renders_with_its_label(): void
    {
        $this->actingAsSuperadmin();

        $user = User::factory()->create();
        $order = Order::factory()->onSite()->for($user)->create(['total_cents' => 9000]);

        // "confirmed" ha il suo tono in $orderTones: qui si verifica che l'ordine esca con la sua etichetta.
        // Il ramo "?? 'muted'" (stato senza tono) non è esercitato da questo test.
        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee(__('admin-people.users.order_statuses.confirmed'));
    }

    public function test_the_partner_box_counts_confirmed_bookings_and_shows_the_payment_mode(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('partner', 'web');

        $partner = User::factory()->create();
        $partner->assignRole('partner');
        PartnerProfile::factory()->offline()->for($partner)->create(['business_name' => 'Agriturismo Offline']);
        $live = Structure::factory()->for($partner)->create();

        // Pagata, confermata in struttura, in attesa: contano le prime due.
        foreach ([Order::factory()->paid()->create(), Order::factory()->onSite()->create(), Order::factory()->create()] as $order) {
            OrderItem::factory()->for($order)->create(['purchasable_id' => $live->id, 'partner_user_id' => $partner->id]);
        }

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee('2 prenotazioni confermate')
            ->assertSee(__('admin-people.users.payment_mode_label'))
            ->assertSee(__('admin-people.users.payment_mode.on_site'));
    }

    public function test_spent_and_paid_orders_ignore_on_site_bookings(): void
    {
        $buyer = User::factory()->create();
        Order::factory()->paid()->for($buyer)->create(['total_cents' => 10000]);
        Order::factory()->onSite()->for($buyer)->create(['total_cents' => 5000]);

        $row = app(UserDirectory::class)->query([])->whereKey($buyer->id)->first();

        // "Speso" sono soldi passati da AnimalAmo: l'ordine in struttura non c'entra.
        $this->assertSame(1, (int) $row->paid_orders_count);
        $this->assertSame(10000, (int) $row->spent_cents);
    }

    public function test_a_customer_profile_has_no_partner_box(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->create();

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertDontSee('Schede a catalogo');
    }

    public function test_the_account_can_be_deactivated_and_reactivated(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->create();

        $component = Livewire::test(UserShow::class, ['user' => $user])
            ->call('toggleActive');
        $this->assertFalse($user->fresh()->is_active);

        $component->call('toggleActive');
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_an_anonymised_account_cannot_be_reactivated(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->inactive()->create();
        $user->forceFill(['anonymized_at' => now()])->save();

        Livewire::test(UserShow::class, ['user' => $user])
            ->assertSee('Dati cancellati su richiesta')
            ->call('toggleActive');

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_anonymise_from_the_profile(): void
    {
        $this->actingAsSuperadmin();
        $user = User::factory()->create(['first_name' => 'Elena']);

        Livewire::test(UserShow::class, ['user' => $user])
            ->call('askAnonymize')
            ->assertSee('Gli ordini restano intatti')
            ->call('anonymize')
            ->assertSee('Dati cancellati su richiesta');

        $this->assertNotNull($user->fresh()->anonymized_at);
    }

    public function test_a_superadmin_has_no_profile_here(): void
    {
        $admin = $this->actingAsSuperadmin();
        Role::findOrCreate('superadmin', 'web');

        $this->get(route('admin.users.show', $admin))->assertNotFound();
    }

    /** Flux::toast non finisce nell'HTML: è un evento `toast-show` con il testo in slots.text. */
    private function toast(string $text): Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    /** Partner online appena iscritto: nessun conto Stripe. */
    private function onlinePartner(): User
    {
        Role::findOrCreate('partner', 'web');

        $partner = User::factory()->create();
        $partner->assignRole('partner');
        PartnerProfile::factory()->for($partner)->create();

        return $partner;
    }

    public function test_the_partner_box_shows_the_stripe_status_and_the_resend_button(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('partner', 'web');

        $cases = [
            'payable' => PartnerProfile::factory()->connected(),
            'incomplete' => PartnerProfile::factory()->state([
                'stripe_account_id' => 'acct_incompleto',
                'stripe_charges_enabled' => true,
                'stripe_payouts_enabled' => false,
            ]),
            'none' => PartnerProfile::factory(),
        ];

        foreach ($cases as $status => $profile) {
            $partner = User::factory()->create();
            $partner->assignRole('partner');
            $profile->for($partner)->create();

            $response = $this->get(route('admin.users.show', $partner))
                ->assertOk()
                ->assertSeeInOrder([__('admin-people.users.stripe_label'), __('admin-people.users.stripe_status.'.$status)])
                ->assertSee(__('admin-people.users.resend_welcome'))
                ->assertSee(__('admin-people.users.payment_mode_change'));

            foreach (array_diff(array_keys($cases), [$status]) as $other) {
                $response->assertDontSee(__('admin-people.users.stripe_status.'.$other));
            }
        }
    }

    public function test_a_partner_without_fiscal_data_is_flagged_and_the_admin_completes_it(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->onlinePartner();
        $partner->partnerProfile->update(['vat' => null, 'tax_code' => null]);

        Livewire::test(UserShow::class, ['user' => $partner])
            ->assertSee(__('admin-people.users.fiscal_missing'))
            ->call('editFiscalData')
            ->assertSet('vat', '')
            ->set('vat', ' 01234567890 ')
            ->set('taxCode', 'GLLMRC80A01B157X')
            ->call('saveFiscalData')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', $this->toast(__('admin-people.users.fiscal_saved')))
            ->assertSee('01234567890')
            ->assertDontSee(__('admin-people.users.fiscal_missing'));

        $profile = $partner->partnerProfile->fresh();
        $this->assertSame('01234567890', $profile->vat);
        $this->assertSame('GLLMRC80A01B157X', $profile->tax_code);
    }

    public function test_emptied_fiscal_data_is_cleared_and_too_long_values_are_refused(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->onlinePartner();
        $partner->partnerProfile->update(['vat' => '01234567890', 'tax_code' => 'GLLMRC80A01B157X']);

        Livewire::test(UserShow::class, ['user' => $partner])
            ->call('editFiscalData')
            ->assertSet('vat', '01234567890')
            ->set('taxCode', str_repeat('X', 17))
            ->call('saveFiscalData')
            ->assertHasErrors(['taxCode' => 'max'])
            ->set('vat', '')
            ->set('taxCode', '')
            ->call('saveFiscalData')
            ->assertHasNoErrors();

        $profile = $partner->partnerProfile->fresh();
        $this->assertNull($profile->vat);
        $this->assertNull($profile->tax_code);
    }

    public function test_a_customer_with_an_orphan_profile_cannot_have_its_fiscal_data_changed(): void
    {
        $this->actingAsSuperadmin();
        $client = User::factory()->create();
        PartnerProfile::factory()->for($client)->create(['vat' => '01234567890']);

        Livewire::test(UserShow::class, ['user' => $client])
            ->assertDontSee(__('admin-people.users.fiscal_label'))
            ->set('vat', '')
            ->call('saveFiscalData');

        $this->assertSame('01234567890', $client->partnerProfile->fresh()->vat);
    }

    public function test_an_offline_partner_without_stripe_cannot_go_back_online(): void
    {
        $this->actingAsSuperadmin();
        $partner = User::factory()->offlinePartner()->create();

        Livewire::test(UserShow::class, ['user' => $partner])
            ->call('editPaymentMode')
            ->assertSet('paymentMode', 'on_site')
            ->set('paymentMode', 'online')
            ->call('setPaymentMode')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', $this->toast(__('partner.payment_mode.errors.stripe_required')));

        $this->assertFalse($partner->partnerProfile->fresh()->online_payment);
    }

    public function test_an_online_partner_can_switch_to_on_site_with_a_link(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->onlinePartner();

        Livewire::test(UserShow::class, ['user' => $partner])
            ->call('editPaymentMode')
            ->assertSet('paymentMode', 'online')
            ->set('paymentMode', 'on_site')
            ->set('paymentUrl', 'https://lecorti.example/prenota')
            ->call('setPaymentMode')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', $this->toast(__('admin-people.users.payment_mode_saved')))
            // Il badge della card, non il testo della modale. assertSeeHtmlInOrder
            // e non assertSeeInOrder: quello non esiste su un componente Livewire e
            // finirebbe sulla risposta JSON, dove le barre del link sono sfuggite (\/).
            ->assertSeeHtmlInOrder([
                __('admin-people.users.payment_mode_label'),
                __('admin-people.users.payment_mode.on_site'),
                'https://lecorti.example/prenota',
                __('admin-people.users.stripe_label'),
            ])
            ->assertDontSee(__('admin-people.users.payment_mode.online'));

        $profile = $partner->partnerProfile->fresh();
        $this->assertFalse($profile->online_payment);
        $this->assertSame('https://lecorti.example/prenota', $profile->payment_url);
    }

    public function test_a_non_http_link_is_refused_on_the_field(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->onlinePartner();

        Livewire::test(UserShow::class, ['user' => $partner])
            ->call('editPaymentMode')
            ->set('paymentMode', 'on_site')
            ->set('paymentUrl', 'javascript:alert(1)')
            ->call('setPaymentMode')
            ->assertHasErrors(['paymentUrl']);

        $profile = $partner->partnerProfile->fresh();
        $this->assertTrue($profile->online_payment);
        $this->assertNull($profile->payment_url);
    }

    public function test_a_customer_with_an_orphan_profile_keeps_its_payment_mode(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('client', 'web');

        // Cliente con un profilo partner rimasto da prima (il caso che
        // PartnerAccountService::create() gestisce): la scheda non mostra la
        // card partner, quindi nemmeno i suoi metodi devono rispondere.
        $client = User::factory()->create();
        $client->assignRole('client');
        PartnerProfile::factory()->for($client)->create();

        Livewire::test(UserShow::class, ['user' => $client])
            ->call('editPaymentMode')
            ->assertNotDispatched('modal-show')
            ->set('paymentMode', 'on_site')
            ->set('paymentUrl', 'https://non-suo.example/prenota')
            ->call('setPaymentMode')
            ->assertNotDispatched('toast-show');

        $profile = $client->partnerProfile->fresh();
        $this->assertTrue($profile->online_payment);
        $this->assertNull($profile->payment_url);
    }

    public function test_the_welcome_link_can_be_sent_again_once_a_minute(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();
        $partner = User::factory()->offlinePartner()->create();

        $component = Livewire::test(UserShow::class, ['user' => $partner])
            ->call('resendWelcome')
            ->assertDispatched('toast-show', $this->toast(__('admin-people.users.welcome_sent', ['email' => $partner->email])));

        Mail::assertSent(PartnerWelcomeMail::class, fn (PartnerWelcomeMail $mail): bool => $mail->hasTo($partner->email) && $mail->setPasswordUrl !== null);

        $component->call('resendWelcome')
            ->assertDispatched('toast-show', $this->toast(__('admin-people.partner_create.errors.throttled')));

        Mail::assertSent(PartnerWelcomeMail::class, 1);
    }

    public function test_a_deactivated_partner_has_no_resend_button(): void
    {
        $this->actingAsSuperadmin();
        $partner = User::factory()->offlinePartner()->inactive()->create();

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertDontSee(__('admin-people.users.resend_welcome'));
    }

    // ── Recapiti pubblici (risposta della cliente, 26/09/2026, punto 6) ─────
    //
    // Sola lettura: li scrive il partner dal suo profilo. L'admin vede cosa ha
    // salvato e se ha dato il consenso, perché senza consenso le schede non li
    // mostrano e da qui si deve poter capire il perché.

    private const PUBLIC_CONTACTS = [
        'public_phone' => '+393331234567',
        'public_whatsapp' => '+393471234567',
        'public_email' => 'info@rifugiodellealpi.it',
        'public_website' => 'https://www.rifugiodellealpi.it',
        'public_address' => 'Piazza Garibaldi 3, Boario Terme',
    ];

    private function partnerWithProfile(array $profile): User
    {
        Role::findOrCreate('partner', 'web');

        $partner = User::factory()->create();
        $partner->assignRole('partner');
        PartnerProfile::factory()->for($partner)->create($profile);

        return $partner;
    }

    public function test_the_partner_box_shows_the_public_contacts_and_the_consent_date(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->partnerWithProfile([
            ...self::PUBLIC_CONTACTS,
            'public_contacts_consent_at' => '2026-09-15 10:30:00',
        ]);

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            // Dopo la riga Stripe, nell'ordine del form del profilo, telefoni leggibili.
            ->assertSeeInOrder([
                __('admin-people.users.stripe_label'),
                __('admin-people.users.public_contacts_label'),
                __('admin-people.users.public_contacts_consent', ['date' => '15 set 2026']),
                __('admin-people.users.public_contacts.phone').':',
                '+39 333 123 4567',
                __('admin-people.users.public_contacts.whatsapp').':',
                '+39 347 123 4567',
                __('admin-people.users.public_contacts.email').':',
                'info@rifugiodellealpi.it',
                __('admin-people.users.public_contacts.website').':',
                'https://www.rifugiodellealpi.it',
                __('admin-people.users.public_contacts.address').':',
                'Piazza Garibaldi 3, Boario Terme',
            ])
            ->assertSee('href="https://www.rifugiodellealpi.it" target="_blank" rel="noopener noreferrer"', escape: false)
            ->assertDontSee(__('admin-people.users.public_contacts_no_consent'));
    }

    /**
     * Senza consenso l'admin vede comunque cosa il partner ha salvato: è il
     * dato che spiega perché la scheda non mostra i recapiti.
     */
    public function test_without_consent_the_saved_contacts_are_listed_under_a_no_consent_badge(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->partnerWithProfile([...self::PUBLIC_CONTACTS, 'public_contacts_consent_at' => null]);

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSeeInOrder([
                __('admin-people.users.public_contacts_label'),
                __('admin-people.users.public_contacts_no_consent'),
                '+39 333 123 4567',
                'Piazza Garibaldi 3, Boario Terme',
            ])
            ->assertDontSee(__('admin-people.users.public_contacts_consent', ['date' => '']));
    }

    /** Solo le voci compilate; nessuna voce = un trattino, non cinque etichette vuote. */
    public function test_only_the_filled_contacts_are_listed(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->partnerWithProfile(['public_email' => 'info@rifugiodellealpi.it']);

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee('info@rifugiodellealpi.it')
            ->assertDontSee(__('admin-people.users.public_contacts.phone').':')
            ->assertDontSee(__('admin-people.users.public_contacts.website').':')
            ->assertDontSeeHtml('<span class="mt-1 block">—</span>');

        $empty = $this->partnerWithProfile([]);

        $this->get(route('admin.users.show', $empty))
            ->assertOk()
            ->assertSeeInOrder([__('admin-people.users.public_contacts_label'), __('admin-people.users.public_contacts_no_consent')])
            ->assertSeeHtml('<span class="mt-1 block">—</span>');
    }

    /**
     * Il form valida il sito http/https, ma una scrittura che lo salta no:
     * l'admin legge il valore com'è, e in un href non ci arriva mai.
     */
    public function test_a_website_that_is_not_http_is_shown_but_never_linked(): void
    {
        $this->actingAsSuperadmin();
        $partner = $this->partnerWithProfile(['public_website' => 'javascript:alert(1)']);

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee('javascript:alert(1)')
            ->assertDontSee('href="javascript:', escape: false);
    }

    /** Il box partner non c'è per un cliente, nemmeno con un profilo rimasto da prima. */
    public function test_a_customer_with_an_orphan_profile_shows_no_public_contacts(): void
    {
        $this->actingAsSuperadmin();
        Role::findOrCreate('client', 'web');

        $client = User::factory()->create();
        $client->assignRole('client');
        PartnerProfile::factory()->withPublicContacts()->for($client)->create();

        $this->get(route('admin.users.show', $client))
            ->assertOk()
            ->assertDontSee(__('admin-people.users.public_contacts_label'))
            ->assertDontSee('+39 333 123 4567');
    }
}
