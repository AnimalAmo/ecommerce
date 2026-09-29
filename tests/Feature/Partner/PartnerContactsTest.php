<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfileInfo;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Partner\PartnerContacts;
use App\Services\Partner\PartnerPaymentModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cosa una scheda può dire del suo partner (risposta della cliente,
 * 26/09/2026, punto 6). Le regole di visibilità stanno SOLO in
 * PartnerContacts e le viste mostrano quello che ricevono, quindi la matrice
 * si prova qui, sul valore di ritorno, e le schede (PayOnSiteNoticeTest) ne
 * provano solo la resa.
 *
 * La matrice ha due assi: il consenso alla pubblicazione e la modalità di
 * incasso. Telefono, WhatsApp, email e sito scavalcano la piattaforma e
 * escono solo per chi NON incassa online; indirizzo pubblico e orari escono
 * in entrambe le modalità. Gli orari non dipendono dal consenso. La sede
 * legale (`partner_profiles.address`) non esce mai.
 */
class PartnerContactsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valori noti e non presi dal faker, così gli href si confrontano per
     * intero. La sede legale ha un nome che non può comparire per caso.
     */
    private const PROFILE = [
        'business_name' => 'Rifugio delle Alpi srl',
        'address' => 'Viale della Sede Legale 10',
        'zip' => '25047',
        'city' => 'Sedelegalopoli',
        'province' => 'BS',
        'payment_url' => 'https://booking.example.com/rifugio',
        'public_phone' => '+393331234567',
        'public_whatsapp' => '+393471234567',
        'public_email' => 'info@rifugiodellealpi.it',
        'public_website' => 'https://www.rifugiodellealpi.it/',
        'public_address' => 'Piazza Garibaldi 3, Boario Terme',
    ];

    /** I quattro link attesi dal PROFILE, nell'ordine della card. */
    private const LINKS = [
        ['type' => 'phone', 'label' => '+39 333 123 4567', 'href' => 'tel:+393331234567'],
        ['type' => 'whatsapp', 'label' => '+39 347 123 4567', 'href' => 'https://wa.me/393471234567'],
        ['type' => 'email', 'label' => 'info@rifugiodellealpi.it', 'href' => 'mailto:info@rifugiodellealpi.it'],
        // Sulla card si legge il dominio, senza schema né barra finale.
        ['type' => 'website', 'label' => 'www.rifugiodellealpi.it', 'href' => 'https://www.rifugiodellealpi.it/'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    private function contacts(): PartnerContacts
    {
        return app(PartnerContacts::class);
    }

    /**
     * Profilo completo. Online = Stripe collegato e pagabile, come chi incassa
     * davvero su AnimalAmo; offline = in struttura, senza Stripe.
     */
    private function profile(bool $online, bool $consent, array $overrides = []): PartnerProfile
    {
        $factory = $online ? PartnerProfile::factory()->connected() : PartnerProfile::factory()->offline();

        return $factory->create(array_merge(self::PROFILE, [
            'opening_hours' => ['it' => 'Lun-Dom 8-20'],
            'public_contacts_consent_at' => $consent ? now() : null,
        ], $overrides));
    }

    /** La sede legale non deve comparire in nessun valore del risultato. */
    private function assertNoLegalAddress(?array $contacts): void
    {
        $flat = (string) json_encode($contacts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertStringNotContainsString('Viale della Sede Legale', $flat);
        $this->assertStringNotContainsString('Sedelegalopoli', $flat);
        $this->assertStringNotContainsString('25047', $flat);
    }

    // ── La matrice: consenso × modalità di incasso ──────────────────────────

    public function test_offline_con_consenso_escono_indirizzo_orari_i_quattro_link_e_la_prenotazione(): void
    {
        $profile = $this->profile(online: false, consent: true);

        $contacts = $this->contacts()->forOwner($profile->user_id);

        $this->assertSame([
            'business_name' => 'Rifugio delle Alpi srl',
            'address' => 'Piazza Garibaldi 3, Boario Terme',
            'opening_hours' => 'Lun-Dom 8-20',
            'links' => self::LINKS,
            'booking_url' => 'https://booking.example.com/rifugio',
        ], $contacts);
        $this->assertNoLegalAddress($contacts);
    }

    /**
     * Senza consenso i recapiti restano salvati ma non escono, né l'indirizzo
     * né i link. Gli orari sì: il partner li scrive sapendo che si vedono. Il
     * link «dove pagare o prenotare» non è un recapito pubblico, resta.
     */
    public function test_offline_senza_consenso_restano_orari_e_prenotazione_ma_niente_indirizzo_ne_link(): void
    {
        $profile = $this->profile(online: false, consent: false);

        $contacts = $this->contacts()->forOwner($profile->user_id);

        $this->assertSame('Rifugio delle Alpi srl', $contacts['business_name']);
        $this->assertNull($contacts['address']);
        $this->assertSame([], $contacts['links']);
        $this->assertSame('Lun-Dom 8-20', $contacts['opening_hours']);
        $this->assertSame('https://booking.example.com/rifugio', $contacts['booking_url']);
        $this->assertNoLegalAddress($contacts);

        // Salvati, non cancellati: al consenso tornano senza riscriverli.
        $this->assertDatabaseHas('partner_profiles', [
            'id' => $profile->id,
            'public_phone' => '+393331234567',
            'public_address' => 'Piazza Garibaldi 3, Boario Terme',
        ]);
    }

    /**
     * Chi incassa su AnimalAmo si prenota dalla scheda: telefono, WhatsApp,
     * email e sito scavalcherebbero la piattaforma. E il link di prenotazione
     * esterno non serve, anche se il profilo ne ha uno rimasto da prima.
     */
    public function test_online_con_consenso_escono_solo_indirizzo_e_orari(): void
    {
        $profile = $this->profile(online: true, consent: true);

        $contacts = $this->contacts()->forOwner($profile->user_id);

        $this->assertSame('Piazza Garibaldi 3, Boario Terme', $contacts['address']);
        $this->assertSame('Lun-Dom 8-20', $contacts['opening_hours']);
        $this->assertSame([], $contacts['links']);
        $this->assertNull($contacts['booking_url']);
        $this->assertNoLegalAddress($contacts);
    }

    public function test_online_senza_consenso_restano_solo_ragione_sociale_e_orari(): void
    {
        $profile = $this->profile(online: true, consent: false);

        $contacts = $this->contacts()->forOwner($profile->user_id);

        $this->assertSame([
            'business_name' => 'Rifugio delle Alpi srl',
            'address' => null,
            'opening_hours' => 'Lun-Dom 8-20',
            'links' => [],
            'booking_url' => null,
        ], $contacts);
    }

    /** Un recapito facoltativo lasciato vuoto non produce un link a metà. */
    public function test_solo_le_voci_compilate_diventano_link(): void
    {
        $profile = $this->profile(online: false, consent: true, overrides: [
            'public_whatsapp' => null,
            'public_website' => null,
        ]);

        $types = array_column($this->contacts()->forOwner($profile->user_id)['links'], 'type');

        $this->assertSame(['phone', 'email'], $types);
    }

    // ── Il service è `scoped`: una modalità cambiata nella stessa richiesta ──
    //
    // PartnerPaymentModeService si ricorda i profili letti per tutta la
    // richiesta, e PartnerContacts legge da lì. Se set() non aggiornasse la
    // copia in memoria, una pagina che cambia modalità e poi si ridisegna
    // (il pannello admin) pubblicherebbe i link del partner appena passato
    // online, o li nasconderebbe a quello appena passato in struttura.

    public function test_passare_online_nella_stessa_richiesta_toglie_subito_i_link(): void
    {
        // Pagabile ma in struttura: può tornare online (canSwitchToOnline).
        $profile = $this->profile(online: true, consent: true, overrides: ['online_payment' => false]);

        $this->assertCount(4, $this->contacts()->forOwner($profile->user_id)['links']);

        app(PartnerPaymentModeService::class)->set($profile->fresh(), true, null);

        $contacts = $this->contacts()->forOwner($profile->user_id);
        $this->assertSame([], $contacts['links']);
        $this->assertNull($contacts['booking_url']);
        $this->assertSame('Piazza Garibaldi 3, Boario Terme', $contacts['address']);
    }

    public function test_passare_in_struttura_nella_stessa_richiesta_mostra_subito_i_link(): void
    {
        $profile = $this->profile(online: true, consent: true, overrides: ['payment_url' => null]);

        $this->assertSame([], $this->contacts()->forOwner($profile->user_id)['links']);

        app(PartnerPaymentModeService::class)->set($profile->fresh(), false, 'https://booking.example.com/nuovo');

        $contacts = $this->contacts()->forOwner($profile->user_id);
        $this->assertSame(self::LINKS, $contacts['links']);
        $this->assertSame('https://booking.example.com/nuovo', $contacts['booking_url']);
    }

    // ── Scritture che saltano il form (seeder, tinker, admin) ───────────────

    /**
     * Il form valida email e sito, ma un href non si fida del form: un
     * `javascript:` o un'email storta scritti dritti a database non arrivano
     * alla card. Gli altri recapiti restano.
     */
    public function test_email_non_valida_e_sito_non_web_scritti_a_database_si_scartano(): void
    {
        $profile = $this->profile(online: false, consent: true, overrides: [
            'public_email' => 'non-una-email',
            'public_website' => 'javascript:alert(1)',
        ]);

        $contacts = $this->contacts()->forOwner($profile->user_id);

        $this->assertSame(['phone', 'whatsapp'], array_column($contacts['links'], 'type'));
        $this->assertStringNotContainsString('javascript:', (string) json_encode($contacts));
        $this->assertStringNotContainsString('non-una-email', (string) json_encode($contacts));
    }

    /** Anche gli altri schemi che il browser esegue o che puntano fuori da http(s). */
    public function test_un_sito_data_o_senza_schema_non_diventa_un_link(): void
    {
        foreach (['data:text/html,<script>alert(1)</script>', '//evil.example', 'www.rifugiodellealpi.it'] as $website) {
            $profile = $this->profile(online: false, consent: true, overrides: ['public_website' => $website]);

            $types = array_column($this->contacts()->forOwner($profile->user_id)['links'], 'type');

            $this->assertNotContains('website', $types, "Il sito «{$website}» non deve finire in un href.");
        }
    }

    /**
     * Trovato dal tester il 28/09/2026. Il form del profilo valida l'email con
     * la regola `email` (RFC), la card la ripulisce con FILTER_VALIDATE_EMAIL,
     * che è più stretto: rifiuta i domini con lettere accentate (IDN, che su
     * .it esistono) e le lettere accentate prima della chiocciola. Il partner
     * scrive «info@caffè.it», spunta il consenso, legge «Salvato» e la sua
     * email non compare mai sulle schede, senza un messaggio. La pulizia qui
     * serve per le scritture che saltano il form (lo dice il docblock di
     * links()), non per scartare quello che il form ha accettato.
     *
     * La prova non sceglie la cura: form e card devono dire la stessa cosa.
     * Se il form la rifiuta, il partner lo legge sul campo e va bene; se la
     * accetta, la card la deve pubblicare.
     */
    public function test_un_email_che_il_form_accetta_arriva_sulla_card(): void
    {
        foreach (['info@caffè.it', 'rené@rifugiodellealpi.it'] as $email) {
            $partner = $this->actingAsOfflinePartner();

            $form = Livewire::test(PartnerProfileInfo::class)
                ->set('form.firstName', 'Mario')
                ->set('form.lastName', 'Rossi')
                ->set('form.businessName', 'Caffè Bau srl')
                ->set('form.address', 'Via Roma 1')
                ->set('form.province', 'PD')
                ->set('form.city', 'Padova')
                ->set('form.zip', '35100')
                ->set('form.vat', '12345678901')
                ->set('form.phone', '3331234567')
                ->set('form.taxCode', 'RSSMRA80A01H501U')
                ->set('form.publicEmail', $email)
                ->set('form.publicContactsConsent', true)
                ->call('save');

            if ($form->errors()->has('form.publicEmail')) {
                // Rifiutata sul campo: il partner lo sa, e niente va a database.
                $form->assertSee($form->errors()->first('form.publicEmail'));
                $this->assertNull($partner->refresh()->partnerProfile->public_email);

                continue;
            }

            $form->assertHasNoErrors();
            $this->assertSame($email, $partner->refresh()->partnerProfile->public_email);

            $this->assertContains(
                'mailto:'.$email,
                array_column($this->contacts()->forOwner($partner->id)['links'], 'href'),
                "Il form ha accettato e salvato «{$email}» col consenso, ma PartnerContacts la scarta: "
                .'FILTER_VALIDATE_EMAIL è più stretto della regola `email` del form.',
            );
        }
    }

    /** Un telefono senza cifre non produce un `tel:` vuoto. */
    public function test_un_telefono_senza_cifre_non_diventa_un_link(): void
    {
        $profile = $this->profile(online: false, consent: true, overrides: [
            'public_phone' => 'chiamare in orario',
            'public_whatsapp' => '   ',
        ]);

        $types = array_column($this->contacts()->forOwner($profile->user_id)['links'], 'type');

        $this->assertSame(['email', 'website'], $types);
    }

    /**
     * Opzione (a) della cliente: mai telefono ed email di registrazione, mai la
     * sede legale. Il caso che lo prova è il consenso dato con le voci
     * pubbliche vuote: è lì che una scorciatoia («se manca il pubblico, usa
     * quello che c'è») ripiegherebbe sui dati dell'account e del profilo
     * fiscale. Partner offline, così anche i link avrebbero via libera.
     */
    public function test_con_consenso_e_voci_vuote_non_si_ripiega_sui_dati_di_registrazione(): void
    {
        $user = User::factory()->create(['email' => 'mario.privato@example.com', 'phone' => '+393339998877']);
        $profile = $this->profile(online: false, consent: true, overrides: [
            'user_id' => $user->id,
            'public_phone' => null,
            'public_whatsapp' => null,
            'public_email' => null,
            'public_website' => null,
            'public_address' => null,
        ]);

        $contacts = $this->contacts()->forOwner($profile->user_id);
        $flat = (string) json_encode($contacts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertNull($contacts['address']);
        $this->assertSame([], $contacts['links']);
        $this->assertStringNotContainsString('mario.privato', $flat);
        $this->assertStringNotContainsString('9998877', $flat);
        $this->assertNoLegalAddress($contacts);
    }

    /**
     * FILTER_VALIDATE_EMAIL (e quindi il form, `email:filter`) ammette ? = & %
     * prima della chiocciola. Concatenato nell'href, «a?bcc=…» diventerebbe un
     * destinatario nascosto nel client di posta di chi scrive (RFC 6068): la
     * parte locale esce codificata, l'etichetta resta quella scritta.
     */
    public function test_l_email_non_puo_aggiungere_destinatari_al_mailto(): void
    {
        $profile = $this->profile(online: false, consent: true, overrides: [
            'public_email' => 'info?bcc=spia@example.com',
        ]);

        $email = collect($this->contacts()->forOwner($profile->user_id)['links'])->firstWhere('type', 'email');

        $this->assertSame('mailto:info%3Fbcc%3Dspia@example.com', $email['href']);
        $this->assertSame('info?bcc=spia@example.com', $email['label']);
    }

    /**
     * Un numero nazionale scritto fuori dal form (seeder, tinker) prende il
     * prefisso italiano: senza, wa.me aprirebbe una chat col paese sbagliato.
     */
    public function test_un_numero_nazionale_scritto_a_database_prende_il_prefisso(): void
    {
        $profile = $this->profile(online: false, consent: true, overrides: [
            'public_phone' => '333 123 4567',
            'public_whatsapp' => '347 1234567',
        ]);

        $links = array_column($this->contacts()->forOwner($profile->user_id)['links'], 'href', 'type');

        $this->assertSame('tel:+393331234567', $links['phone']);
        $this->assertSame('https://wa.me/393471234567', $links['whatsapp']);
    }

    // ── Chi non ha niente da dire ────────────────────────────────────────────

    public function test_un_partner_senza_profilo_non_ha_contatti(): void
    {
        $owner = User::factory()->create();
        $structure = Structure::factory()->create(['user_id' => $owner->id]);

        $this->assertNull($this->contacts()->forOwner($owner->id));
        $this->assertNull($this->contacts()->forPurchasable($structure));
        $this->assertNull($this->contacts()->forOwner(null));
        $this->assertNull($this->contacts()->forPurchasable(null));
    }

    /**
     * Un profilo che non ha niente di pubblicabile: nessuna ragione sociale,
     * nessun orario, recapiti senza consenso, incasso online. Null, così la
     * card resta alla sola dicitura e il riquadro «Informazioni utili» non si
     * apre vuoto.
     */
    public function test_un_profilo_senza_niente_da_pubblicare_torna_null(): void
    {
        $profile = $this->profile(online: true, consent: false, overrides: [
            'business_name' => null,
            'opening_hours' => null,
        ]);

        $this->assertNull($this->contacts()->forOwner($profile->user_id));
    }

    /** forPurchasable() legge il titolare dalla scheda e dà gli stessi contatti. */
    public function test_la_scheda_da_gli_stessi_contatti_del_suo_titolare(): void
    {
        $profile = $this->profile(online: false, consent: true);
        $structure = Structure::factory()->create(['user_id' => $profile->user_id]);

        $this->assertSame(
            $this->contacts()->forOwner($profile->user_id),
            $this->contacts()->forPurchasable($structure),
        );
    }

    // ── Orari tradotti ───────────────────────────────────────────────────────

    /** Lingua corrente, poi l'italiano (Translatable::fallback): mai il JSON grezzo. */
    public function test_gli_orari_seguono_la_lingua_e_ripiegano_sull_italiano(): void
    {
        $both = $this->profile(online: true, consent: false, overrides: [
            'opening_hours' => ['it' => 'Lun-Ven 9-18', 'en' => 'Mon-Fri 9-18'],
        ]);
        $italianOnly = $this->profile(online: true, consent: false, overrides: [
            'opening_hours' => ['it' => 'Sab-Dom 10-17'],
        ]);

        app()->setLocale('en');

        $this->assertSame('Mon-Fri 9-18', $this->contacts()->forOwner($both->user_id)['opening_hours']);
        $this->assertSame('Sab-Dom 10-17', $this->contacts()->forOwner($italianOnly->user_id)['opening_hours']);
    }

    // ── La regola del riquadro «Informazioni utili» ─────────────────────────

    public function test_has_public_info_guarda_indirizzo_e_orari(): void
    {
        $this->assertFalse(PartnerContacts::hasPublicInfo(null));
        $this->assertFalse(PartnerContacts::hasPublicInfo(['business_name' => 'Solo il nome', 'address' => null, 'opening_hours' => null]));

        $this->assertTrue(PartnerContacts::hasPublicInfo(['address' => 'Piazza Garibaldi 3', 'opening_hours' => null]));
        $this->assertTrue(PartnerContacts::hasPublicInfo(['address' => null, 'opening_hours' => 'Lun-Dom 8-20']));

        // Sulle attività gli orari stanno già fra le informazioni della scheda:
        // da soli non aprono il riquadro, l'indirizzo sì.
        $this->assertFalse(PartnerContacts::hasPublicInfo(['address' => null, 'opening_hours' => 'Lun-Dom 8-20'], withHours: false));
        $this->assertTrue(PartnerContacts::hasPublicInfo(['address' => 'Piazza Garibaldi 3', 'opening_hours' => null], withHours: false));
    }
}
