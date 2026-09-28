<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityName;
use App\Livewire\Partner\Activity\ActivityType;
use App\Livewire\Partner\CreateService;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Livewire\Partner\Smartbox\SmartboxType;
use App\Livewire\Partner\Structure\StructureType;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "Indietro" dal primo step di ogni famiglia. Portava sempre a "Crea
 * servizio", che apre una bozza nuova: chi stava modificando un servizio da
 * "I miei servizi" perdeva in silenzio la bozza, e continuando ne creava un
 * altro invece di modificare quello pubblicato.
 */
class WizardBackLinkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * href del pulsante "Indietro" dello step. L'header dell'area partner
     * linka sempre sia "Crea servizio" sia "I miei servizi": cercare l'href
     * in tutta la pagina darebbe ragione a qualunque pulsante.
     */
    private function backHref(string $html): ?string
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $buttons = (new DOMXPath($dom))->query('//main//a[@data-flux-button]');
        $this->assertSame(1, $buttons->length, 'Atteso un solo pulsante-link nello step: "Indietro".');

        return $buttons->item(0)->getAttribute('href');
    }

    /** @return array<string, array{0: class-string, 1: string, 2: string, 3: string}> */
    public static function families(): array
    {
        return [
            'struttura' => [StructureType::class, 'struttura', 'hotel', 'partner.structure.type'],
            'attivita' => [ActivityType::class, 'attivita', 'attivita', 'partner.activity.type'],
            'smartbox' => [SmartboxType::class, 'smartbox', 'soggiorno', 'partner.smartbox.type'],
        ];
    }

    private function serviceOf(User $partner, string $category, string $type, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => $category,
            'type' => $type,
            'name' => ['it' => 'Servizio pubblicato'],
        ], $attributes));
    }

    #[DataProvider('families')]
    public function test_chi_modifica_un_servizio_torna_alla_lista(string $component, string $category, string $type, string $firstStep): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, $category, $type);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $draft->id)
            ->assertRedirect(route($firstStep));

        $this->assertSame(route('partner.services'), $this->backHref(Livewire::test($component)->html()));

        // Nessuna bozza nuova: la sessione punta ancora al servizio in modifica.
        $this->assertSame($draft->id, session('structure_draft_id'));
        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }

    #[DataProvider('families')]
    public function test_chi_modifica_una_bozza_in_attesa_torna_alla_lista(string $component, string $category, string $type, string $firstStep): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, $category, $type, [
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->assertSame(route('partner.services'), $this->backHref(Livewire::test($component)->html()));
    }

    #[DataProvider('families')]
    public function test_un_servizio_nuovo_torna_alla_scelta_del_servizio(string $component, string $category, string $type, string $firstStep): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 0,
            'service_category' => $category,
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->assertSame(route('partner.service.create'), $this->backHref(Livewire::test($component)->html()));
    }

    // ── Difetto W4: l'«Indietro» dello step Nome è cablato sullo step del tipo ─
    //
    // `CreateService::next()` reindirizza DIRETTAMENTE a `partner.activity.name`
    // per le card «Servizio professionale» ed «Evento», saltando lo step del
    // tipo. Il pulsante «Indietro» di ActivityName è però un href fisso a
    // `partner.activity.type` — l'unico back link del wizard che non passa da
    // `serviceChoiceBackUrl()`. Lo skip funziona in una sola direzione: chi torna
    // indietro atterra sullo «Step 1 di 10», la scelta Attività/Evento che il
    // funnel aveva già fatto, e non esiste ritorno alle card.

    /** @return array<string, array{0: string}> */
    public static function cardsThatSkipTheTypeStep(): array
    {
        return [
            'servizio professionale' => ['servizi'],
            'evento' => ['eventi'],
        ];
    }

    #[DataProvider('cardsThatSkipTheTypeStep')]
    public function test_lo_step_del_nome_torna_alle_card_quando_e_il_primo_del_percorso(string $card): void
    {
        $this->actingAsActivePartner();

        Livewire::test(CreateService::class)
            ->set('service', $card)
            ->call('next')
            ->assertRedirect(route('partner.activity.name'));

        $this->assertSame(
            route('partner.service.create'),
            $this->backHref(Livewire::test(ActivityName::class)->html()),
            'La bozza arriva qui con current_step 1 e senza essere passata da activity.type: '
            .'«Indietro» deve riportare alla scelta delle card, non a uno step che il funnel ha saltato.',
        );
    }

    /**
     * Chi sta MODIFICANDO un servizio di «I miei servizi» deve tornare alla
     * lista anche da questo step: «Crea servizio» gli aprirebbe una bozza nuova
     * e la modifica si perderebbe in silenzio. È la stessa regola dei tre step
     * del tipo, che questo componente non applica.
     */
    public function test_chi_modifica_un_servizio_torna_alla_lista_anche_dallo_step_del_nome(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, 'attivita', 'attivita');
        session(['structure_draft_id' => $draft->id]);

        $this->assertSame(route('partner.services'), $this->backHref(Livewire::test(ActivityName::class)->html()));
    }

    /**
     * Nota per chi corregge: la bozza delle card «Attività» e «Servizio
     * professionale» arriva qui indistinguibile (entrambe `service_category`
     * 'attivita', `type` 'attivita', `current_step` 1), quindi non esiste un
     * criterio sui dati per riportare una allo step del tipo e l'altra alle
     * card. La destinazione unica sensata è quella di `serviceChoiceBackUrl()`,
     * che è anche la sola che non atterra su uno step già superato.
     */
    public function test_lo_step_del_nome_non_rimanda_mai_a_uno_step_che_il_funnel_ha_saltato(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(CreateService::class)
            ->set('service', 'eventi')
            ->call('next');

        $this->assertNotSame(
            route('partner.activity.type'),
            $this->backHref(Livewire::test(ActivityName::class)->html()),
            'Chi entra dalla card «Evento» ha già fatto la scelta Attività/Evento: '
            .'rimandarlo sullo «Step 1 di 10» gli chiede due volte la stessa cosa.',
        );
    }

    // ── Giro del tester, 28/09/2026: l'«Indietro» dello step Nome da ogni ingresso ─
    //
    // Le prove W4 qui sopra guardano l'href. Queste seguono il partner anche
    // DOPO il clic: le card devono riprendere la stessa bozza (niente orfane,
    // difetto W2) con la card giusta già scelta, e il nome scritto deve restare.

    #[DataProvider('cardsThatSkipTheTypeStep')]
    public function test_tornando_alle_card_dallo_step_del_nome_si_riprende_la_stessa_bozza(string $card): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(CreateService::class)->set('service', $card)->call('next');
        Livewire::test(ActivityName::class)
            ->set('name.it', 'Servizio in corso')
            ->call('next')
            ->assertHasNoErrors();
        $draftId = session('structure_draft_id');

        $this->assertSame(route('partner.service.create'), $this->backHref(Livewire::test(ActivityName::class)->html()));

        // «Evento» si riconosce dai dati; «Servizio professionale» scrive la
        // stessa bozza della card «Attività» e le card mostrano quella
        // (CreateService::cardOf, limite dichiarato dalla lane nome).
        Livewire::test(CreateService::class)
            ->assertSet('service', $card === 'eventi' ? 'eventi' : 'attivita')
            ->call('next')
            ->assertRedirect(route($card === 'eventi' ? 'partner.activity.name' : 'partner.activity.type'));

        $this->assertSame($draftId, session('structure_draft_id'));
        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
        $this->assertSame('Servizio in corso', StructureDraft::findOrFail($draftId)->getTranslation('name', 'it'));
    }

    /** Card «Attività»: passa dallo step del tipo, e dal Nome torna comunque alle card, sulla stessa bozza. */
    public function test_dalla_card_attivita_lo_step_del_nome_torna_alle_card_sulla_stessa_bozza(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(CreateService::class)
            ->set('service', 'attivita')
            ->call('next')
            ->assertRedirect(route('partner.activity.type'));
        Livewire::test(ActivityType::class)
            ->set('type', 'attivita')
            ->call('next')
            ->assertRedirect(route('partner.activity.name'));
        Livewire::test(ActivityName::class)
            ->set('name.it', 'Toelettatura Bau')
            ->call('next')
            ->assertHasNoErrors();
        $draftId = session('structure_draft_id');

        $this->assertSame(route('partner.service.create'), $this->backHref(Livewire::test(ActivityName::class)->html()));

        Livewire::test(CreateService::class)
            ->assertSet('service', 'attivita')
            ->call('next')
            ->assertRedirect(route('partner.activity.type'));

        $this->assertSame($draftId, session('structure_draft_id'));
        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }

    /** «Modifica» di un evento pubblicato: dal Nome si torna alla lista, non alle card che aprirebbero un servizio nuovo. */
    public function test_chi_modifica_un_evento_torna_alla_lista_dallo_step_del_nome(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, 'attivita', 'eventi');

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $draft->id)
            ->assertRedirect(route('partner.activity.type'));

        $this->assertSame(route('partner.services'), $this->backHref(Livewire::test(ActivityName::class)->html()));
        $this->assertSame($draft->id, session('structure_draft_id'));
    }

    public function test_chi_modifica_un_evento_in_attesa_torna_alla_lista_dallo_step_del_nome(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, 'attivita', 'eventi', [
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->assertSame(route('partner.services'), $this->backHref(Livewire::test(ActivityName::class)->html()));
    }

    /**
     * «Riprendi» di una bozza in corso entrata dalla card «Evento» (nome già
     * salvato): risalendo fino allo step Nome, il suo «Indietro» porta alle
     * card, che la riprendono invece di aprirne una nuova.
     */
    public function test_chi_riprende_una_bozza_in_corso_dal_nome_torna_alle_card_senza_perderla(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, 'attivita', 'eventi', [
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => StructureDraft::STARTED_STEP,
        ]);

        Livewire::test(PartnerMyServices::class)->call('resume', $draft->id);
        $this->assertSame($draft->id, session('structure_draft_id'));

        $this->assertSame(route('partner.service.create'), $this->backHref(Livewire::test(ActivityName::class)->html()));

        Livewire::test(CreateService::class)->assertSet('service', 'eventi');

        $this->assertSame($draft->id, session('structure_draft_id'));
        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }
}
