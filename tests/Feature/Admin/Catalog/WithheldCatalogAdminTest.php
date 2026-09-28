<?php

namespace Tests\Feature\Admin\Catalog;

use App\Livewire\Admin\Catalog\CatalogIndex;
use App\Models\Event\Event;
use App\Models\Partner\PartnerProfile;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\Catalog\CatalogAdmin;
use App\Services\Admin\Catalog\CatalogPresenter;
use App\Services\Admin\Dashboard\DashboardOverview;
use App\Services\Admin\People\UserDirectory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Difetto F4 (audit 27/09/2026, corretto il 28/09/2026): il pannello non
 * conosceva il ritiro, e una smartbox `withheld_at` usciva «Pubblicata».
 *
 * La sezione «Difetto F4» di CatalogAdminTest prova stato, filtro
 * «Pubblicate», lista e `totals()`. Qui lo stato «ritirata» in ogni vista
 * admin che la correzione ha toccato — filtro «Ritirate», intestazione,
 * scheda, home, riquadro partner, esportazione — e l'ordine dei badge quando
 * una scheda è ritirata E sospesa o in attesa.
 *
 * Scritto dal tester il 28/09/2026.
 */
class WithheldCatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
        $this->actingAsSuperadmin();
    }

    public function test_il_filtro_ritirate_mostra_solo_le_ritirate_anche_se_sospese(): void
    {
        // Titoli disgiunti (review del 28/09/2026): con «Cofanetto ritirato»
        // sottostringa dell'altro, il primo assertSee passava anche senza di lui.
        $this->withheldBox('Cofanetto solo ritirato');
        $this->withheldBox('Cofanetto sospeso e ritirato', ['suspended_at' => now()]);
        SmartboxPackage::factory()->create(['title' => ['it' => 'Cofanetto in vetrina']]);
        Structure::factory()->create(['name' => ['it' => 'Hotel sospeso'], 'suspended_at' => now()]);

        Livewire::test(CatalogIndex::class)
            ->assertSee(__('admin-catalog.status_filter.withheld'))
            ->set('status', CatalogAdmin::STATUS_WITHHELD)
            ->assertSee('Cofanetto solo ritirato')
            ->assertSee('Cofanetto sospeso e ritirato')
            ->assertDontSee('Cofanetto in vetrina')
            ->assertDontSee('Hotel sospeso');
    }

    /**
     * L'ordine dei badge scelto dalla lane: in attesa > modifiche chieste >
     * sospesa > ritirata > pubblicata. Una scheda sospesa e ritirata dice
     * «Sospesa» accanto al bottone «Riattiva»; il ritiro lo dice l'avviso.
     */
    public function test_la_sospensione_e_l_attesa_vincono_sul_ritiro_nel_badge(): void
    {
        $catalog = app(CatalogAdmin::class);

        $this->assertSame(CatalogAdmin::STATUS_SUSPENDED, $catalog->status($this->withheldBox('A', ['suspended_at' => now()])));
        $this->assertSame(CatalogAdmin::STATUS_PENDING, $catalog->status($this->withheldBox('B', ['approval_status' => 'pending'])));
        $this->assertSame(CatalogAdmin::STATUS_CHANGES, $catalog->status($this->withheldBox('C', ['approval_status' => 'changes_requested'])));
        $this->assertSame(CatalogAdmin::STATUS_WITHHELD, $catalog->status($this->withheldBox('D')));
    }

    public function test_l_intestazione_nomina_le_ritirate_solo_quando_ci_sono(): void
    {
        SmartboxPackage::factory()->create();

        $withheldNote = trans_choice('admin-catalog.index.withheld', 1, ['count' => 1]);

        $this->get(route('admin.catalog.index'))
            ->assertOk()
            ->assertDontSee($withheldNote);

        $this->withheldBox('Cofanetto ritirato');

        $this->get(route('admin.catalog.index'))
            ->assertOk()
            ->assertSee('2 schede, 0 sospese, '.$withheldNote.'.');
    }

    /** La scheda dice perché è fuori dal sito, qualunque sia il badge. */
    public function test_la_scheda_ritirata_spiega_il_ritiro_anche_se_sospesa(): void
    {
        $withheld = $this->withheldBox('Cofanetto ritirato', ['suspended_at' => now()]);
        $normal = SmartboxPackage::factory()->create();

        $this->get(route('admin.catalog.show', ['type' => 'smartbox_package', 'id' => $withheld->id]))
            ->assertOk()
            ->assertSee(__('admin-catalog.show.withheld_heading'))
            // Il badge resta quello della sospensione, col suo «Riattiva».
            ->assertSee(__('admin-catalog.show.reactivate'))
            // Fuori dal sito: nessun «Vedi sul sito» verso una pagina che dà 404.
            ->assertDontSee(__('admin-catalog.show.view_on_site'));

        $this->get(route('admin.catalog.show', ['type' => 'smartbox_package', 'id' => $normal->id]))
            ->assertOk()
            ->assertDontSee(__('admin-catalog.show.withheld_heading'))
            ->assertSee(__('admin-catalog.show.view_on_site'));

        // Ritirata e basta: stesso avviso, e niente «Vedi sul sito».
        $onlyWithheld = $this->withheldBox('Cofanetto solo ritirato');

        $this->get(route('admin.catalog.show', ['type' => 'smartbox_package', 'id' => $onlyWithheld->id]))
            ->assertOk()
            ->assertSee(__('admin-catalog.show.withheld_heading'))
            ->assertDontSee(__('admin-catalog.show.view_on_site'));
    }

    public function test_la_home_non_conta_le_ritirate_fra_le_pubblicate_e_le_nomina(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-21 08:00:00', 'UTC'));
        Structure::factory()->create();
        $this->withheldBox('Cofanetto ritirato');

        $overview = app(DashboardOverview::class);
        $this->assertSame(['published' => 1, 'suspended' => 0], $overview->catalogCounts());
        $this->assertSame(1, $overview->catalogWithheld());

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertSee(trans_choice('admin-dashboard.home.kpi.catalog_note_withheld', 1, ['count' => 1]))
            // Il riquadro «Ultime schede» usa lo stesso presenter: badge «Ritirata», non «Pubblicata».
            ->assertSeeInOrder(['Cofanetto ritirato', __('admin-catalog.status.withheld')]);
    }

    public function test_senza_ritirate_la_home_non_aggiunge_la_nota(): void
    {
        Structure::factory()->create();

        $this->get(route('admin.home'))
            ->assertOk()
            ->assertDontSee(trans_choice('admin-dashboard.home.kpi.catalog_note_withheld', 1, ['count' => 1]));
    }

    public function test_il_riquadro_del_partner_dice_quante_schede_sono_ritirate(): void
    {
        Role::findOrCreate('partner', 'web');
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        PartnerProfile::factory()->offline()->for($partner)->create();

        Structure::factory()->for($partner)->create();
        $this->withheldBox('Cofanetto ritirato', ['user_id' => $partner->id]);

        $this->assertSame(1, app(UserDirectory::class)->partnerSummary($partner)['withheld']);

        $this->get(route('admin.users.show', $partner))
            ->assertOk()
            ->assertSee('2 schede, '.trans_choice('admin-people.users.listings_withheld', 1, ['count' => 1]));
    }

    public function test_l_esportazione_dice_ritirata_e_non_pubblicata(): void
    {
        $this->withheldBox('Cofanetto ritirato');

        $body = $this->get(route('admin.catalog.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('Cofanetto ritirato', $body);
        $this->assertStringContainsString(__('admin-catalog.status.withheld'), $body);
        $this->assertStringNotContainsString(__('admin-catalog.status.published'), $body);
    }

    /** La ricerca globale del pannello usa lo stesso presenter: anche lì «Ritirata». */
    public function test_la_ricerca_globale_dice_ritirata(): void
    {
        $this->withheldBox('Cofanetto ritirato');

        $this->get(route('admin.search', ['q' => 'Cofanetto']))
            ->assertOk()
            ->assertSeeInOrder(['Cofanetto ritirato', __('admin-catalog.status.withheld')])
            ->assertDontSee(__('admin-catalog.status.published'));
    }

    /** Il presenter porta il ritiro a parte, indipendente dal badge (serve all'avviso della scheda). */
    public function test_la_riga_porta_il_ritiro_anche_quando_il_badge_e_un_altro(): void
    {
        $row = app(CatalogPresenter::class)->row($this->withheldBox('Cofanetto', ['suspended_at' => now()]));

        $this->assertTrue($row['withheld']);
        $this->assertTrue($row['suspended']);
        $this->assertSame(CatalogAdmin::STATUS_SUSPENDED, $row['status']);
    }

    /** Il ritiro è delle smartbox, ma la colonna è su tutte e tre le tabelle: il pannello le guarda tutte. */
    public function test_i_contatori_guardano_il_ritiro_su_tutte_e_tre_le_famiglie(): void
    {
        $event = Event::factory()->create();
        $event->forceFill(['withheld_at' => now()])->save();
        $this->withheldBox('Cofanetto ritirato');

        $this->assertSame(2, app(CatalogAdmin::class)->totals()['withheld']);
        $this->assertSame(2, app(DashboardOverview::class)->catalogWithheld());
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /** Smartbox ritirata dalla piattaforma, riletta dal database. */
    private function withheldBox(string $title, array $attributes = []): SmartboxPackage
    {
        $box = SmartboxPackage::factory()->create(['title' => ['it' => $title], ...$attributes]);
        $box->forceFill(['withheld_at' => now()])->save();

        return SmartboxPackage::withHidden()->findOrFail($box->id);
    }
}
