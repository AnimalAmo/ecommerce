<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityCancellation;
use App\Livewire\Partner\Smartbox\SmartboxPrice;
use App\Livewire\Partner\Structure\HotelPayment;
use App\Models\Partner\PartnerProfile;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cosa succede all'ultimo step. Un partner online senza Stripe prima vedeva
 * un toast perso nel redirect, la bozza restava in sessione e spariva da
 * "I miei servizi". Ora resta in attesa con un segnale esplicito e un avviso
 * che la dashboard mostra.
 */
class CompleteDraftOutcomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('it');
    }

    private function actingAsUnpayablePartner(): User
    {
        $partner = $this->actingAsActivePartner();
        PartnerProfile::factory()->for($partner)->create();

        return $partner;
    }

    private function draftInSession(User $partner, array $attributes): StructureDraft
    {
        $draft = StructureDraft::create(array_merge([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
        ], $attributes));
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    /** Hotel pubblicabile fermo alle foto (step 10), come prima dell'ultimo step. */
    private function hotelOf(User $partner, array $attributes = []): StructureDraft
    {
        return $this->draftInSession($partner, array_merge([
            'current_step' => 10,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Zampa Felice'],
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']],
        ], $attributes));
    }

    public function test_lo_skip_dell_hotel_senza_stripe_mette_la_bozza_in_attesa(): void
    {
        $partner = $this->actingAsUnpayablePartner();
        $draft = $this->hotelOf($partner);

        Livewire::test(HotelPayment::class)
            ->call('skip')
            ->assertRedirect(route('partner.dashboard'))
            ->assertNotDispatched('toast-show');

        $fresh = $draft->fresh();
        $this->assertSame(StructureDraft::STATUS_DRAFT, $fresh->status);
        // skip() non salva lo step: prima restava a 10, indistinguibile da un abbandono.
        $this->assertSame(11, $fresh->current_step);
        $this->assertTrue($fresh->isAwaitingPublication());
        $this->assertNull(session('structure_draft_id'));
        $this->assertSame(__('partner.publish.awaiting_stripe'), session('partner.notice'));
        $this->assertSame(0, Structure::withHidden()->where('structure_draft_id', $draft->id)->count());
    }

    public function test_l_attivita_senza_stripe_chiude_allo_step_undici(): void
    {
        $partner = $this->actingAsUnpayablePartner();
        $draft = $this->draftInSession($partner, [
            'current_step' => 9,
            'service_category' => 'attivita',
            'type' => 'attivita',
            'name' => ['it' => 'Passeggiata a sei zampe'],
            'date_start' => '2026-10-10',
        ]);

        Livewire::test(ActivityCancellation::class)
            ->set('when', '7')
            ->call('next')
            ->assertRedirect(route('partner.dashboard'));

        $fresh = $draft->fresh();
        $this->assertSame('7', $fresh->cancellation_when);
        $this->assertSame(11, $fresh->current_step);
        $this->assertTrue($fresh->isAwaitingPublication());
    }

    public function test_la_smartbox_senza_stripe_chiude_allo_step_dodici(): void
    {
        $partner = $this->actingAsUnpayablePartner();
        $draft = $this->draftInSession($partner, [
            'current_step' => 11,
            'service_category' => 'smartbox',
            'type' => 'soggiorno',
            'name' => ['it' => 'Cofanetto relax'],
        ]);

        Livewire::test(SmartboxPrice::class)
            ->set('price', '99')
            ->call('save')
            ->assertRedirect(route('partner.dashboard'));

        $fresh = $draft->fresh();
        $this->assertSame(12, $fresh->current_step);
        $this->assertTrue($fresh->isAwaitingPublication());
    }

    public function test_la_modifica_di_un_servizio_pubblicato_senza_stripe_resta_completata(): void
    {
        $partner = $this->actingAsUnpayablePartner();
        $draft = $this->hotelOf($partner, ['status' => StructureDraft::STATUS_COMPLETED, 'current_step' => 11]);

        Livewire::test(HotelPayment::class)->call('skip')->assertRedirect(route('partner.dashboard'));

        $fresh = $draft->fresh();
        $this->assertSame(StructureDraft::STATUS_COMPLETED, $fresh->status);
        $this->assertTrue($fresh->isAwaitingPublication());
        // Il servizio c'è già: l'avviso parla delle modifiche, non di un servizio nuovo.
        $this->assertSame(__('partner.publish.awaiting_stripe_changes'), session('partner.notice'));
    }

    public function test_una_bozza_incompleta_resta_sullo_step_con_l_errore(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->hotelOf($partner, ['rooms' => null]);

        Livewire::test(HotelPayment::class)
            ->call('skip')
            ->assertNoRedirect()
            ->assertDispatched('toast-show', fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === __('partner.errors.draft_not_publishable')
                && ($params['dataset']['variant'] ?? null) === 'danger');

        // La sessione resta: il partner torna indietro a correggere la stessa bozza.
        $this->assertSame($draft->id, session('structure_draft_id'));
        $this->assertSame(StructureDraft::STATUS_DRAFT, $draft->fresh()->status);
        $this->assertNull(session('partner.notice'));
    }

    public function test_la_dashboard_mostra_l_avviso_dopo_la_chiusura_in_attesa(): void
    {
        // Il flash c'è già (test sopra), ma la dashboard lo legge solo dal
        // Task 7 di P4 (Dashboard::render, variabile `$notice`): lì va tolto lo skip.
        $this->markTestSkipped('La dashboard mostra partner.notice dal Task 7 di P4.');

        $partner = $this->actingAsUnpayablePartner();
        $this->hotelOf($partner);

        Livewire::test(HotelPayment::class)->call('skip');

        $this->get(route('partner.dashboard'))
            ->assertOk()
            ->assertSee(__('partner.publish.awaiting_stripe'));
    }
}
