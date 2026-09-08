<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxStructures;
use App\Models\Favorite\Favorite;
use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\Structure\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Il mockup XD riempiva l'area B2B di dati finti: tre statistiche fisse in
 * dashboard (112 vendute, 21 annullate, 236 salvate) e cinque hotel inventati
 * nello step smartbox. Su animalamo.it quei numeri e quei nomi non sono di
 * nessuno: il primo partner che entra si vedrebbe attribuire vendite mai fatte
 * e potrebbe pubblicare un cofanetto che rimanda a strutture inesistenti.
 */
class PartnerDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Dashboard: statistiche
    |--------------------------------------------------------------------------
    */

    public function test_a_partner_without_sales_sees_zeros_and_no_invented_numbers(): void
    {
        $this->actingAsActivePartner();

        $html = $this->get(route('partner.dashboard'))->assertOk()->getContent();

        $this->assertSame('0', $this->statValue($html, __('partner.dashboard.stat_sold')));
        $this->assertSame('0', $this->statValue($html, __('partner.dashboard.stat_cancelled')));
        $this->assertSame('0', $this->statValue($html, __('partner.dashboard.stat_saved')));

        // Le variazioni % del mockup non hanno una fonte (non esiste uno storico
        // da confrontare): inventarle sarebbe lo stesso difetto dei valori.
        $this->assertStringNotContainsString('2.9%', $html);
        $this->assertStringNotContainsString('0.7%', $html);
        $this->assertStringNotContainsString('4.2%', $html);
    }

    public function test_stats_count_only_the_bookings_and_favourites_of_the_partner(): void
    {
        $partner = $this->actingAsActivePartner();
        $mine = Structure::factory()->create(['user_id' => $partner->id]);

        // Prodotto di un ALTRO partner: venduto, annullato e salvato, ma non mio.
        $theirs = Structure::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->booking($mine, paid: true);
        $this->booking($mine, paid: true);
        $this->booking($mine, paid: false);
        $this->booking($theirs, paid: true);

        Favorite::factory()->create(['favoritable_type' => 'structure', 'favoritable_id' => $mine->id]);
        Favorite::factory()->create(['favoritable_type' => 'structure', 'favoritable_id' => $theirs->id]);

        $html = $this->get(route('partner.dashboard'))->assertOk()->getContent();

        $this->assertSame('2', $this->statValue($html, __('partner.dashboard.stat_sold')));
        $this->assertSame('1', $this->statValue($html, __('partner.dashboard.stat_cancelled')));
        $this->assertSame('1', $this->statValue($html, __('partner.dashboard.stat_saved')));
    }

    /**
     * Le righe del catalogo mock non hanno proprietario (`user_id` NULL): senza
     * guard, `where('user_id', null)` diventa `IS NULL` e le attribuirebbe al
     * partner loggato.
     */
    public function test_stats_ignore_catalogue_rows_without_an_owner(): void
    {
        $this->actingAsActivePartner();
        $orphan = Structure::factory()->create(['user_id' => null]);

        $this->booking($orphan, paid: true);
        Favorite::factory()->create(['favoritable_type' => 'structure', 'favoritable_id' => $orphan->id]);

        $html = $this->get(route('partner.dashboard'))->assertOk()->getContent();

        $this->assertSame('0', $this->statValue($html, __('partner.dashboard.stat_sold')));
        $this->assertSame('0', $this->statValue($html, __('partner.dashboard.stat_saved')));
    }

    /*
    |--------------------------------------------------------------------------
    | Smartbox step 10: strutture selezionabili
    |--------------------------------------------------------------------------
    */

    public function test_a_partner_without_structures_sees_no_mock_names(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.smartbox.structures'))
            ->assertOk()
            ->assertDontSee('Hotel Milano')
            ->assertDontSee('Hotel Brescia')
            ->assertDontSee('Lamasu W&amp;R', escape: false)
            ->assertDontSee('Santa Margherita Ligure')
            ->assertSee(__('partner.smartbox_structures.empty_title'));
    }

    public function test_the_step_lists_only_the_published_structures_of_the_partner(): void
    {
        $partner = $this->actingAsActivePartner();

        Structure::factory()->create([
            'user_id' => $partner->id,
            'name' => 'Rifugio del Cane',
            'location' => 'Bormio (SO)',
        ]);
        Structure::factory()->create(['user_id' => User::factory()->create()->id, 'name' => 'Villa Altrui']);
        Structure::factory()->create(['user_id' => null, 'name' => 'Struttura Catalogo Mock']);

        $this->get(route('partner.smartbox.structures'))
            ->assertOk()
            ->assertSee('Rifugio del Cane')
            ->assertSee('Bormio (SO)')
            ->assertDontSee('Villa Altrui')
            ->assertDontSee('Struttura Catalogo Mock')
            ->assertDontSee(__('partner.smartbox_structures.empty_title'));
    }

    public function test_the_step_rejects_a_structure_that_is_not_the_partners(): void
    {
        $this->actingAsActivePartner();
        $theirs = Structure::factory()->create(['user_id' => User::factory()->create()->id]);

        Livewire::test(SmartboxStructures::class)
            ->set('structures', [(string) $theirs->id])
            ->call('next')
            ->assertHasErrors('structures.0');
    }

    /** Riga ordine sul prodotto indicato, in un ordine pagato o annullato. */
    private function booking(Structure $structure, bool $paid): OrderItem
    {
        $order = Order::factory()->create(['status' => $paid ? 'paid' : 'cancelled']);

        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'purchasable_type' => 'structure',
            'purchasable_id' => $structure->id,
        ]);
    }

    /**
     * Valore mostrato nella card della statistica: il testo dell'elemento che
     * segue l'etichetta. Un assertDontSee('112') non basterebbe — passerebbe
     * anche con un altro numero inventato al suo posto.
     */
    private function statValue(string $html, string $label): ?string
    {
        preg_match('/'.preg_quote($label, '/').'<\/p>\s*<(?:p|span|div)[^>]*>\s*([^<]*?)\s*</s', $html, $matches);

        return $matches[1] ?? null;
    }
}
