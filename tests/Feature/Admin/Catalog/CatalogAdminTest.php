<?php

namespace Tests\Feature\Admin\Catalog;

use App\Enums\OrderStatus;
use App\Livewire\Admin\Catalog\Approvals;
use App\Livewire\Admin\Catalog\CatalogIndex;
use App\Livewire\Admin\Catalog\CatalogShow;
use App\Mail\CatalogModerationMail;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\Catalog\CatalogAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsSuperadmin();
    }

    public function test_the_list_shows_the_three_families_including_hidden_rows(): void
    {
        Structure::factory()->create(['name' => ['it' => 'Hotel Brescia']]);
        Event::factory()->create(['title' => ['it' => 'Puppy Yoga'], 'suspended_at' => now()]);
        SmartboxPackage::factory()->create(['title' => ['it' => 'Relax in Lombardia']]);

        $this->get(route('admin.catalog.index'))
            ->assertOk()
            ->assertSee('Hotel Brescia')
            ->assertSee('Puppy Yoga')
            ->assertSee('Relax in Lombardia')
            ->assertSee('3 schede, 1 sospesa.');
    }

    // ── Difetto F4: il pannello è cieco al ritiro ─────────────────────────────
    //
    // `withheld_at` non compare in tutto app/Services/Admin e app/Livewire/Admin.
    // `CatalogAdmin::status()` fa match solo su `approval_status` e
    // `suspended_at`, quindi una scheda ritirata esce `published` col badge
    // verde, rientra nel filtro «pubblicate» e non entra in nessun contatore —
    // mentre CatalogVisibleScope la nasconde al sito e il partner legge «Serve il
    // sistema di pagamento».

    /** Smartbox ritirata da noi (non sospesa dall'admin): `withheld_at` valorizzato. */
    private function withheldSmartbox(string $title = 'Cofanetto ritirato'): SmartboxPackage
    {
        $box = SmartboxPackage::factory()->create(['title' => ['it' => $title]]);
        $box->forceFill(['withheld_at' => now()])->save();

        return SmartboxPackage::withHidden()->findOrFail($box->id);
    }

    public function test_una_scheda_ritirata_non_risulta_pubblicata(): void
    {
        $box = $this->withheldSmartbox();

        $this->assertTrue($box->isWithheld(), 'La fixture deve essere davvero ritirata.');

        $this->assertNotSame(
            CatalogAdmin::STATUS_PUBLISHED,
            app(CatalogAdmin::class)->status($box),
            'Una scheda che il sito non mostra non può avere il badge verde «Pubblicata»: '
            .'lo scope e la diagnostica partner conoscono già il ritiro, il pannello no.',
        );
    }

    public function test_il_filtro_pubblicate_non_include_una_scheda_ritirata(): void
    {
        $this->withheldSmartbox('Cofanetto ritirato');
        SmartboxPackage::factory()->create(['title' => ['it' => 'Cofanetto in vetrina']]);

        Livewire::test(CatalogIndex::class)
            ->set('status', CatalogAdmin::STATUS_PUBLISHED)
            ->assertSee('Cofanetto in vetrina')
            ->assertDontSee('Cofanetto ritirato');
    }

    /**
     * Quello che l'admin vede aprendo il catalogo: una riga verde «Pubblicata»
     * per una scheda che non è in vetrina da nessuna parte.
     */
    public function test_la_lista_non_dichiara_pubblicata_una_scheda_ritirata(): void
    {
        $this->withheldSmartbox('Cofanetto ritirato');

        $this->get(route('admin.catalog.index'))
            ->assertOk()
            ->assertSee('Cofanetto ritirato')
            ->assertDontSee(__('admin-catalog.status.published'));
    }

    /**
     * I contatori dell'intestazione: una scheda ritirata è dentro `total` e
     * fuori da `suspended` e `pending`, quindi «1 scheda» senza una parola su
     * quella che non si vede.
     */
    public function test_i_contatori_del_catalogo_distinguono_una_scheda_ritirata(): void
    {
        $this->withheldSmartbox();

        $totals = app(CatalogAdmin::class)->totals();

        $this->assertArrayHasKey(
            'withheld',
            $totals,
            'Serve un contatore del ritiro accanto a sospese e in attesa: senza, la riga di '
            .'intestazione conta una scheda che nessuno può vedere come se fosse in vetrina.',
        );
        $this->assertSame(1, $totals['withheld']);
    }

    public function test_filters_narrow_the_list(): void
    {
        Structure::factory()->create(['name' => ['it' => 'Hotel Brescia']]);
        Event::factory()->create(['title' => ['it' => 'Puppy Yoga'], 'suspended_at' => now()]);

        Livewire::test(CatalogIndex::class)
            ->set('status', 'suspended')
            ->assertSee('Puppy Yoga')
            ->assertDontSee('Hotel Brescia')
            ->set('status', '')
            ->set('search', 'brescia')
            ->assertSee('Hotel Brescia')
            ->assertDontSee('Puppy Yoga')
            ->set('search', '')
            ->set('family', 'event')
            ->assertSee('Puppy Yoga')
            ->assertDontSee('Hotel Brescia');
    }

    public function test_suspend_and_reactivate_go_through_the_confirmation(): void
    {
        $structure = Structure::factory()->create(['name' => ['it' => 'Hotel Brescia']]);

        Livewire::test(CatalogIndex::class)
            ->call('askSuspend', 'structure', $structure->id)
            ->assertSet('confirming.action', 'suspend')
            ->assertSee('Sospendere questa scheda?')
            ->call('confirmAction')
            ->assertSet('confirming', null);

        $this->assertNotNull(Structure::withHidden()->find($structure->id)->suspended_at);

        Livewire::test(CatalogIndex::class)
            ->call('askSuspend', 'structure', $structure->id)
            ->assertSet('confirming.action', 'reactivate')
            ->call('confirmAction');

        $this->assertNull(Structure::withHidden()->find($structure->id)->suspended_at);
    }

    public function test_delete_is_blocked_by_a_future_paid_booking_and_offers_to_suspend(): void
    {
        $structure = Structure::factory()->create();
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        OrderItem::factory()->for($order)->create([
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'booked_from' => now()->addDays(10),
            'booked_until' => now()->addDays(12),
        ]);

        Livewire::test(CatalogIndex::class)
            ->call('askDelete', 'structure', $structure->id)
            ->assertSet('confirming.action', 'suspend')
            ->assertSet('confirming.confirm', 'Sospendi invece')
            ->assertSee('1 prenotazione futura')
            ->call('confirmAction');

        $this->assertNotNull(Structure::withHidden()->find($structure->id), 'non cancellata');
        $this->assertNotNull(Structure::withHidden()->find($structure->id)->suspended_at, 'sospesa al suo posto');
    }

    public function test_delete_is_blocked_by_a_future_on_site_booking(): void
    {
        $structure = Structure::factory()->create();
        $order = Order::factory()->onSite()->create();
        OrderItem::factory()->for($order)->create([
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'booked_from' => now()->addDays(10),
            'booked_until' => now()->addDays(12),
        ]);

        $catalog = app(CatalogAdmin::class);

        // Il cliente arriverà in struttura: la scheda non può sparire.
        $this->assertSame(['total' => 1, 'future' => 1], $catalog->bookings($structure));
        $this->assertNotNull($catalog->deletionBlocker($structure));
    }

    public function test_delete_removes_the_row_and_what_points_to_it(): void
    {
        $structure = Structure::factory()->create();
        Favorite::factory()->create(['favoritable_type' => 'structure', 'favoritable_id' => $structure->id]);
        $pastOrder = Order::factory()->create(['status' => OrderStatus::Paid]);
        $pastItem = OrderItem::factory()->for($pastOrder)->create([
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
            'booked_from' => now()->subDays(20),
            'booked_until' => now()->subDays(18),
        ]);

        Livewire::test(CatalogIndex::class)
            ->call('askDelete', 'structure', $structure->id)
            ->assertSet('confirming.action', 'delete')
            ->call('confirmAction');

        $this->assertNull(Structure::withHidden()->find($structure->id));
        $this->assertSame(0, Favorite::query()->count());
        $this->assertNotNull(OrderItem::find($pastItem->id), "l'ordine resta");
    }

    public function test_the_detail_saves_texts_and_prices(): void
    {
        $structure = Structure::factory()->create(['name' => ['it' => 'Vecchio nome'], 'price_cents' => 10000]);

        Livewire::test(CatalogShow::class, ['type' => 'structure', 'id' => $structure->id])
            ->assertSet('name.it', 'Vecchio nome')
            ->assertSet('price', '100')
            ->set('name.it', 'Nuovo nome')
            ->set('name.en', 'New name')
            ->set('description.it', 'Descrizione aggiornata.')
            ->set('price', '120,50')
            ->set('supplement', '15')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = Structure::withHidden()->find($structure->id);
        $this->assertSame('Nuovo nome', $fresh->getTranslation('name', 'it'));
        $this->assertSame('New name', $fresh->getTranslation('name', 'en'));
        $this->assertSame(12050, $fresh->price_cents);
        $this->assertSame(1500, $fresh->animal_supplement_cents);
    }

    public function test_the_detail_rejects_a_missing_italian_name(): void
    {
        $event = Event::factory()->create();

        Livewire::test(CatalogShow::class, ['type' => 'event', 'id' => $event->id])
            ->set('name.it', '')
            ->call('save')
            ->assertHasErrors('name.it');
    }

    public function test_errors_from_generic_rules_are_in_italian(): void
    {
        // `integer` e `max.numeric` non avevano un testo in lang/it/validation.php:
        // il pannello mostrava quello inglese del framework.
        $structure = Structure::factory()->create();

        Livewire::test(CatalogShow::class, ['type' => 'structure', 'id' => $structure->id])
            ->set('cancellationDays', 'tre')
            ->call('save')
            ->assertHasErrors(['cancellationDays' => 'Inserisci un numero intero.'])
            ->set('cancellationDays', '400')
            ->call('save')
            ->assertHasErrors(['cancellationDays' => 'Il valore non può superare 365.']);
    }

    public function test_an_unknown_family_is_a_404(): void
    {
        $this->get('/admin/catalog/order/1')->assertNotFound();
    }

    public function test_export_is_a_csv_excel_can_open(): void
    {
        Structure::factory()->create(['name' => ['it' => 'Hotel Brescia']]);

        $response = $this->get(route('admin.catalog.export'));

        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('Scheda;Tipo;Partner', $body);
        $this->assertStringContainsString('Hotel Brescia', $body);
    }

    public function test_approving_publishes_and_tells_the_partner(): void
    {
        Mail::fake();
        $partner = User::factory()->create(['email' => 'partner@example.com']);
        $structure = Structure::factory()->create(['user_id' => $partner->id, 'name' => ['it' => 'Le Corti']]);
        $structure->forceFill(['approval_status' => 'pending', 'approval_requested_at' => now()])->save();

        Livewire::test(Approvals::class)
            ->assertSee('Le Corti')
            ->call('approve', 'structure', $structure->id);

        $this->assertTrue(Structure::query()->whereKey($structure->id)->exists(), 'visibile sul sito');
        Mail::assertQueued(CatalogModerationMail::class, fn ($mail) => $mail->outcome === 'approved' && $mail->hasTo('partner@example.com'));
    }

    public function test_requesting_changes_needs_a_note_and_mails_it(): void
    {
        Mail::fake();
        $partner = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $partner->id]);
        $event->forceFill(['approval_status' => 'pending'])->save();

        Livewire::test(Approvals::class)
            ->call('askChanges', 'event', $event->id)
            ->call('requestChanges')
            ->assertHasErrors('note')
            ->set('note', 'Le foto sono sfocate, rifalle per favore.')
            ->call('requestChanges')
            ->assertHasNoErrors();

        $fresh = Event::withHidden()->find($event->id);
        $this->assertSame('changes_requested', $fresh->approval_status);
        $this->assertSame('Le foto sono sfocate, rifalle per favore.', $fresh->approval_note);
        Mail::assertQueued(CatalogModerationMail::class, fn ($mail) => $mail->outcome === 'changes_requested');
    }
}
