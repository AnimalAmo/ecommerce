<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfileInfo;
use App\Models\Partner\PartnerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerProfileInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_personal_and_fiscal_fields(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile'))
            ->assertOk()
            ->assertSee(__('partner.profile.info_heading'))
            ->assertSee(__('partner.profile.business_name'))
            ->assertSee(__('partner.profile.vat'))
            ->assertDontSee('PEC')
            ->assertDontSee('SDI')
            ->assertSee(__('partner.profile.save'));
    }

    public function test_it_rehydrates_the_partner_data(): void
    {
        $partner = $this->actingAsActivePartner(['first_name' => 'Susanna', 'last_name' => 'Rossi']);
        $partner->partnerProfile()->create(['business_name' => 'Hotel Rosovino', 'vat' => '86334519757']);

        Livewire::test(PartnerProfileInfo::class)
            ->assertSet('form.firstName', 'Susanna')
            ->assertSet('form.businessName', 'Hotel Rosovino')
            ->assertSet('form.vat', '86334519757');
    }

    public function test_save_requires_the_mandatory_fields(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.firstName', '')
            ->set('form.businessName', '')
            ->set('form.vat', '')
            ->call('save')
            ->assertHasErrors(['form.firstName', 'form.businessName', 'form.vat']);
    }

    public function test_save_rejects_an_email_already_in_use(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.email', 'taken@example.com')
            ->call('save')
            ->assertHasErrors(['form.email']);
    }

    public function test_save_persists_personal_and_fiscal_data(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.city', 'Padova')
            ->set('form.zip', '35100')
            ->set('form.vat', '12345678901')
            ->set('form.phone', '3331234567')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $partner->id,
            'first_name' => 'Mario',
            'email' => 'mario@example.com',
        ]);
        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'business_name' => 'Pet Hotel Srl',
            'vat' => '12345678901',
            'city' => 'Padova',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Orari di apertura o disponibilità (risposta della cliente, 27/09/2026)
    |--------------------------------------------------------------------------
    | Sono sul profilo e non sulla bozza perché la cliente li nomina in due
    | punti — fra i recapiti pubblici e fra i campi dell'attività — e due campi
    | che possono contraddirsi sono peggio di uno. Le schede li leggono da qui,
    | passando da PartnerContacts.
    */

    /** I campi ci sono, con il loro avviso: sono facoltativi e si vedono a catalogo. */
    private function fillRequired(Testable $component): Testable
    {
        return $component
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.city', 'Padova')
            ->set('form.zip', '35100')
            ->set('form.vat', '12345678901')
            ->set('form.phone', '3331234567')
            ->set('form.taxCode', 'RSSMRA80A01H501U');
    }

    public function test_page_renders_the_opening_hours_field(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile'))
            ->assertOk()
            ->assertSee(__('partner.profile.opening_hours'))
            ->assertSee(__('partner.profile.opening_hours_hint'));
    }

    public function test_save_persists_the_opening_hours_in_both_languages(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.openingHours.it', 'Lun-Ven 9-18')
            ->set('form.openingHours.en', 'Mon-Fri 9-18')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame('Lun-Ven 9-18', $profile->getTranslation('opening_hours', 'it'));
        $this->assertSame('Mon-Fri 9-18', $profile->getTranslation('opening_hours', 'en'));
    }

    public function test_it_rehydrates_the_saved_opening_hours(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create([
            'business_name' => 'Toelettatura Bau',
            'opening_hours' => ['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'],
        ]);

        Livewire::test(PartnerProfileInfo::class)
            ->assertSet('form.openingHours.it', 'Lun-Sab 8-20')
            ->assertSet('form.openingHours.en', 'Mon-Sat 8-20');
    }

    /** «Eventuali»: un profilo salvato senza orari resta valido e la colonna vuota. */
    public function test_the_opening_hours_are_optional(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([], $partner->refresh()->partnerProfile->getTranslations('opening_hours'));
    }

    /** 200 caratteri come gli altri testi liberi localizzati del partner; 201 no. */
    public function test_opening_hours_longer_than_the_limit_are_refused(): void
    {
        $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.openingHours.it', str_repeat('a', 201))
            ->call('save')
            ->assertHasErrors(['form.openingHours.it' => 'max']);
    }

    // ── Giro del tester, 28/09/2026: W5 sugli orari del profilo ──────────────
    //
    // `toProfile()` va a `updateOrCreate()`, che fonde sul profilo esistente:
    // con `array_filter` gli orari inglesi salvati una volta restavano per
    // sempre sulla scheda /en. La scheda li legge dal profilo, non da una copia.

    public function test_svuotare_gli_orari_inglesi_li_toglie_e_litaliano_resta(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create([
            'business_name' => 'Toelettatura Bau',
            'opening_hours' => ['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'],
        ]);

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->assertSet('form.openingHours.en', 'Mon-Sat 8-20')
            ->set('form.openingHours.en', '')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame(['it' => 'Lun-Sab 8-20'], $profile->getTranslations('opening_hours'));
        $this->assertSame('Lun-Sab 8-20', $profile->getTranslation('opening_hours', 'en'));
    }

    public function test_svuotare_gli_orari_in_tutte_le_lingue_li_toglie(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create([
            'business_name' => 'Toelettatura Bau',
            'opening_hours' => ['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'],
        ]);

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.openingHours.it', '')
            ->set('form.openingHours.en', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([], $partner->refresh()->partnerProfile->getTranslations('opening_hours'));
    }

    /*
    |--------------------------------------------------------------------------
    | Recapiti pubblici (risposta della cliente, 26/09/2026, punto 6)
    |--------------------------------------------------------------------------
    | Voci NUOVE, tutte facoltative, mai il telefono o l'email di registrazione
    | né la sede legale. Si pubblicano solo con la spunta di consenso, che è una
    | data: quella del PRIMO consenso, conservata ai salvataggi successivi e
    | azzerata togliendo la spunta. Cosa si vede su quale scheda lo provano
    | PartnerContactsTest e PayOnSiteNoticeTest; qui il form e il salvataggio.
    */

    /** Recapiti validi, nella forma che manda la pagina (i telefoni li ricompone x-phone-input). */
    private function fillPublicContacts(Testable $component): Testable
    {
        return $component
            ->set('form.publicPhone', '+393331234567')
            ->set('form.publicWhatsapp', '+393471234567')
            ->set('form.publicEmail', 'info@rifugiodellealpi.it')
            ->set('form.publicWebsite', 'https://www.rifugiodellealpi.it')
            ->set('form.publicAddress', 'Piazza Garibaldi 3, Boario Terme');
    }

    public function test_page_renders_the_public_contacts_fields(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile'))
            ->assertOk()
            ->assertSee(__('partner.profile.public_contacts_heading'))
            ->assertSee(__('partner.profile.public_contacts_intro'))
            ->assertSee(__('partner.profile.public_phone'))
            ->assertSee(__('partner.profile.public_whatsapp'))
            ->assertSee(__('partner.profile.public_email'))
            ->assertSee(__('partner.profile.public_website'))
            ->assertSee(__('partner.profile.public_address'))
            ->assertSee(__('partner.profile.public_contacts_consent'))
            ->assertSeeHtml('wire:model="form.publicEmail"')
            ->assertSeeHtml('wire:model="form.publicWebsite"')
            ->assertSeeHtml('wire:model="form.publicAddress"')
            ->assertSeeHtml('wire:model="form.publicContactsConsent"')
            // «WhatsApp» compare anche nel testo introduttivo, quindi l'etichetta
            // da sola non prova il campo: x-phone-input rende il wire:key dal
            // nome della proprietà, ed è quello che dice dove scrive.
            ->assertSeeHtml('wire:key="phone-input-formpublicphone"')
            ->assertSeeHtml('wire:key="phone-input-formpublicwhatsapp"');
    }

    public function test_save_persists_every_public_contact(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 10:00:00'));
        $partner = $this->actingAsActivePartner();

        $this->fillPublicContacts($this->fillRequired(Livewire::test(PartnerProfileInfo::class)))
            ->set('form.publicContactsConsent', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame('+393331234567', $profile->public_phone);
        $this->assertSame('+393471234567', $profile->public_whatsapp);
        $this->assertSame('info@rifugiodellealpi.it', $profile->public_email);
        $this->assertSame('https://www.rifugiodellealpi.it', $profile->public_website);
        $this->assertSame('Piazza Garibaldi 3, Boario Terme', $profile->public_address);
        $this->assertTrue($profile->publishesContacts());
        $this->assertSame('2026-09-28 10:00:00', $profile->public_contacts_consent_at->toDateTimeString());
    }

    /**
     * Voci nuove, non copie: il cellulare e l'email di registrazione restano
     * su users e non diventano recapiti pubblici, nemmeno col consenso.
     */
    public function test_the_registration_phone_and_email_are_not_public_contacts(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.email', 'mario.privato@example.com')
            ->set('form.publicContactsConsent', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertTrue($profile->publishesContacts());
        $this->assertNull($profile->public_phone);
        $this->assertNull($profile->public_email);
        $this->assertSame('mario.privato@example.com', $partner->email);
    }

    /**
     * Telefono e WhatsApp in E.164 come il cellulare personale: è la forma che
     * serve a `tel:` e `wa.me` sulle schede. Un numero nazionale o con gli
     * spazi, incollato o scritto da un client che salta x-phone-input, arriva
     * a database già normalizzato.
     */
    public function test_public_phones_are_stored_in_e164(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.publicPhone', '333 123 4567')
            ->set('form.publicWhatsapp', '+39 347 123 4567')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame('+393331234567', $profile->public_phone);
        $this->assertSame('+393471234567', $profile->public_whatsapp);
    }

    public function test_it_rehydrates_the_public_contacts_and_the_consent(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->withPublicContacts()->for($partner)->create([
            'public_email' => 'info@rifugiodellealpi.it',
            'public_website' => 'https://www.rifugiodellealpi.it',
            'public_address' => 'Piazza Garibaldi 3, Boario Terme',
        ]);

        Livewire::test(PartnerProfileInfo::class)
            ->assertSet('form.publicPhone', '+393331234567')
            ->assertSet('form.publicWhatsapp', '+393471234567')
            ->assertSet('form.publicEmail', 'info@rifugiodellealpi.it')
            ->assertSet('form.publicWebsite', 'https://www.rifugiodellealpi.it')
            ->assertSet('form.publicAddress', 'Piazza Garibaldi 3, Boario Terme')
            ->assertSet('form.publicContactsConsent', true);
    }

    public function test_a_profile_without_consent_rehydrates_an_empty_checkbox(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->withPublicContacts()->for($partner)->create(['public_contacts_consent_at' => null]);

        Livewire::test(PartnerProfileInfo::class)
            ->assertSet('form.publicPhone', '+393331234567')
            ->assertSet('form.publicContactsConsent', false);
    }

    /** Tutte facoltative: un profilo senza recapiti si salva e le colonne restano null, non ''. */
    public function test_the_public_contacts_are_optional_and_blank_means_null(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'public_phone' => null,
            'public_whatsapp' => null,
            'public_email' => null,
            'public_website' => null,
            'public_address' => null,
            'public_contacts_consent_at' => null,
        ]);
    }

    /** Svuotare una voce la toglie davvero: updateOrCreate() fonde, e '' non è «voce non data». */
    public function test_clearing_a_public_contact_removes_it(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->withPublicContacts()->for($partner)->create();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.publicEmail', '')
            ->set('form.publicWhatsapp', '')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertNull($profile->public_email);
        $this->assertNull($profile->public_whatsapp);
        $this->assertSame('+393331234567', $profile->public_phone);
    }

    public function test_an_invalid_public_email_is_refused(): void
    {
        $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.publicEmail', 'non-una-email')
            ->call('save')
            ->assertHasErrors(['form.publicEmail' => 'email']);
    }

    /**
     * Il sito finisce in un href su una pagina pubblica: solo http(s). Uno
     * schema che il browser esegue, un altro protocollo o un indirizzo senza
     * schema si fermano sul campo.
     */
    public function test_a_public_website_that_is_not_http_is_refused(): void
    {
        $this->actingAsActivePartner();

        foreach (['javascript:alert(1)', 'ftp://rifugiodellealpi.it', 'www.rifugiodellealpi.it'] as $website) {
            $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
                ->set('form.publicWebsite', $website)
                ->call('save')
                ->assertHasErrors(['form.publicWebsite']);
        }
    }

    public function test_invalid_public_phones_are_refused(): void
    {
        $this->actingAsActivePartner();

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.publicPhone', '12345')
            ->set('form.publicWhatsapp', 'non un numero')
            ->call('save')
            ->assertHasErrors(['form.publicPhone', 'form.publicWhatsapp']);
    }

    public function test_a_rejected_submit_saves_nothing(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillPublicContacts($this->fillRequired(Livewire::test(PartnerProfileInfo::class)))
            ->set('form.publicWebsite', 'javascript:alert(1)')
            ->set('form.publicContactsConsent', true)
            ->call('save')
            ->assertHasErrors(['form.publicWebsite']);

        $this->assertNull($partner->refresh()->partnerProfile);
    }

    /**
     * Campi composti a mano (flux:field + flux:label + flux:input): Flux non
     * inietta l'errore, e senza `<flux:error>` un invio rifiutato ridisegna la
     * pagina identica. L'errore deve comparire nella pagina, per ogni voce.
     */
    public function test_the_public_contacts_errors_are_shown_on_the_page(): void
    {
        $this->actingAsActivePartner();

        $component = $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.publicPhone', '12345')
            ->set('form.publicWhatsapp', '12345')
            ->set('form.publicEmail', 'non-una-email')
            ->set('form.publicWebsite', 'javascript:alert(1)')
            ->set('form.publicAddress', str_repeat('a', 201))
            ->call('save');

        foreach (['publicPhone', 'publicWhatsapp', 'publicEmail', 'publicWebsite', 'publicAddress'] as $field) {
            $message = $component->errors()->first('form.'.$field);

            $this->assertNotSame('', $message, "Manca l'errore di form.{$field}.");
            $component->assertSee($message);
        }
    }

    /**
     * Telefono e WhatsApp hanno lo stesso messaggio («Inserisci un numero di
     * telefono valido.», senza :attribute), quindi nel test qui sopra l'errore
     * dell'uno basta a far passare il controllo dell'altro. Uno alla volta, il
     * messaggio in pagina può venire solo dal `<flux:error>` di quel campo.
     */
    public function test_each_public_phone_shows_its_own_error(): void
    {
        $this->actingAsActivePartner();

        foreach (['publicPhone' => 'publicWhatsapp', 'publicWhatsapp' => 'publicPhone'] as $invalid => $valid) {
            $component = $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
                ->set('form.'.$invalid, '12345')
                ->set('form.'.$valid, '+393331234567')
                ->call('save')
                ->assertHasErrors(['form.'.$invalid])
                ->assertHasNoErrors(['form.'.$valid]);

            $component->assertSee($component->errors()->first('form.'.$invalid));
        }
    }

    // ── Il consenso: una data, quella del primo «sì» ─────────────────────────

    public function test_the_contacts_are_saved_even_without_consent(): void
    {
        $partner = $this->actingAsActivePartner();

        $this->fillPublicContacts($this->fillRequired(Livewire::test(PartnerProfileInfo::class)))
            ->set('form.publicContactsConsent', false)
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame('+393331234567', $profile->public_phone);
        $this->assertSame('Piazza Garibaldi 3, Boario Terme', $profile->public_address);
        $this->assertNull($profile->public_contacts_consent_at);
        $this->assertFalse($profile->publishesContacts());
    }

    /**
     * La data dice QUANDO il partner ha acconsentito, non quando ha salvato
     * l'ultima volta: un nuovo salvataggio con la spunta ancora data non la
     * sposta, anche se cambia un recapito.
     */
    public function test_the_first_consent_date_is_kept_on_the_next_save(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->withPublicContacts()->for($partner)->create([
            'public_contacts_consent_at' => Carbon::parse('2026-09-01 09:30:00'),
        ]);
        $this->travelTo(Carbon::parse('2026-09-28 10:00:00'));

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->assertSet('form.publicContactsConsent', true)
            ->set('form.publicPhone', '+393401112222')
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertSame('+393401112222', $profile->public_phone);
        $this->assertSame('2026-09-01 09:30:00', $profile->public_contacts_consent_at->toDateTimeString());
    }

    /**
     * Lo stesso percorso dall'inizio: primo salvataggio con la spunta, poi un
     * secondo, giorni dopo, da una pagina nuova. Ogni salvataggio vero è una
     * richiesta a sé con l'utente riletto dalla sessione, e qui lo si rilegge
     * allo stesso modo: l'istanza di actingAs() si terrebbe in memoria il
     * profilo della prima richiesta, che in produzione non esiste.
     */
    public function test_the_consent_date_survives_a_later_visit(): void
    {
        $partner = $this->actingAsActivePartner();
        $this->travelTo(Carbon::parse('2026-09-10 08:00:00'));

        $this->fillPublicContacts($this->fillRequired(Livewire::test(PartnerProfileInfo::class)))
            ->set('form.publicContactsConsent', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->travelTo(Carbon::parse('2026-09-28 18:00:00'));
        $this->actingAs($partner->fresh());

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->assertSet('form.publicContactsConsent', true)
            ->set('form.publicAddress', 'Via Nuova 1, Boario Terme')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            '2026-09-10 08:00:00',
            $partner->refresh()->partnerProfile->public_contacts_consent_at->toDateTimeString(),
        );
    }

    /** Spunta tolta: la data se ne va, i recapiti restano salvati ma non si pubblicano. */
    public function test_withdrawing_the_consent_clears_the_date_and_keeps_the_contacts(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->withPublicContacts()->for($partner)->create([
            'public_contacts_consent_at' => Carbon::parse('2026-09-01 09:30:00'),
        ]);

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.publicContactsConsent', false)
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertNull($profile->public_contacts_consent_at);
        $this->assertFalse($profile->publishesContacts());
        $this->assertSame('+393331234567', $profile->public_phone);
        $this->assertSame('+393471234567', $profile->public_whatsapp);
    }

    /** Un nuovo consenso dopo il ritiro riparte con la sua data, non con quella vecchia. */
    public function test_a_new_consent_after_withdrawal_gets_a_new_date(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->withPublicContacts()->for($partner)->create(['public_contacts_consent_at' => null]);
        $this->travelTo(Carbon::parse('2026-09-28 10:00:00'));

        $this->fillRequired(Livewire::test(PartnerProfileInfo::class))
            ->set('form.publicContactsConsent', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            '2026-09-28 10:00:00',
            $partner->refresh()->partnerProfile->public_contacts_consent_at->toDateTimeString(),
        );
    }

    /**
     * `toProfile()` va a updateOrCreate(), che fonde sul profilo esistente:
     * salvare i recapiti non deve toccare quello che il form non possiede.
     * Gli orari li riscrive uguali (sono nel form), la modalità di incasso e
     * il link di prenotazione non ci sono proprio e devono restare.
     */
    public function test_saving_the_contacts_keeps_the_opening_hours_and_the_payment_mode(): void
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->offline()->for($partner)->create([
            'payment_url' => 'https://booking.example.com/rifugio',
            'opening_hours' => ['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'],
        ]);

        $this->fillPublicContacts($this->fillRequired(Livewire::test(PartnerProfileInfo::class)))
            ->set('form.publicContactsConsent', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = $partner->refresh()->partnerProfile;
        $this->assertFalse($profile->online_payment);
        $this->assertSame('https://booking.example.com/rifugio', $profile->payment_url);
        $this->assertSame(['it' => 'Lun-Sab 8-20', 'en' => 'Mon-Sat 8-20'], $profile->getTranslations('opening_hours'));
        $this->assertSame('+393331234567', $profile->public_phone);
    }
}
