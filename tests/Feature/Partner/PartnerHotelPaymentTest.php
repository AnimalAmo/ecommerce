<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfilePayment;
use App\Livewire\Partner\Structure\HotelPayment;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_payment_fields(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsActivePartner();

        $this->get(route('partner.structure.hotel.payment'))
            ->assertOk()
            ->assertSee(__('partner.hotel_payment.heading'))
            ->assertSee(__('partner.hotel_payment.step'))
            ->assertSee(__('partner.hotel_payment.account_holder'))
            ->assertSee(__('partner.hotel_payment.iban'))
            ->assertSee(__('partner.hotel_payment.later'))
            ->assertSee(__('partner.hotel_payment.next'));
    }

    public function test_next_requires_the_fields(): void
    {
        Livewire::test(HotelPayment::class)
            ->call('next')
            ->assertHasErrors(['form.accountHolder', 'form.iban', 'form.bic']);
    }

    /**
     * Bozza hotel pubblicabile (nome + stanze) del partner, in sessione come
     * nel wizard. Una bozza vuota ora è un errore (DraftNotPublishableException)
     * e non più una bozza "completed" senza riga a catalogo.
     */
    private function publishableDraftOf(User $partner): StructureDraft
    {
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 10,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Zampa Felice'],
            'rooms' => [['type' => 'doppia', 'count' => 2, 'price' => '75']],
        ]);
        session(['structure_draft_id' => $draft->id]);

        return $draft;
    }

    public function test_next_saves_and_completes_to_the_dashboard(): void
    {
        // La bozza va a catalogo solo se il partner può essere pagato.
        $this->publishableDraftOf($this->actingAsPayablePartner());

        Livewire::test(HotelPayment::class)
            ->set('form.accountHolder', 'Mario Rossi')
            ->set('form.iban', 'IT60X0542811101000000123456')
            ->set('form.bic', 'UNCRITMM')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $this->assertDatabaseHas('structure_drafts', [
            'account_holder' => 'Mario Rossi',
            'status' => 'completed',
            'current_step' => 11,
        ]);
    }

    public function test_skip_completes_the_draft_and_redirects(): void
    {
        // La bozza va a catalogo solo se il partner può essere pagato.
        $this->publishableDraftOf($this->actingAsPayablePartner());

        Livewire::test(HotelPayment::class)
            ->call('skip')
            ->assertRedirect(route('partner.dashboard'));

        $this->assertDatabaseHas('structure_drafts', ['status' => 'completed']);
    }

    public function test_it_rehydrates_the_saved_iban(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 11, 'iban' => 'IT99']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelPayment::class)->assertSet('form.iban', 'IT99');
    }

    // ── Difetto W7: le coordinate restano solo sulla bozza ────────────────────
    //
    // `HotelPaymentForm::toDraft()` scrive `structure_drafts.account_holder/iban/
    // bic` e nessuna riga tocca `partner_profiles`: il partner digita l'IBAN allo
    // step 11, apre Profilo → Metodo di pagamento e lo trova vuoto, quindi lo
    // riscrive. Due copie che non si sincronizzano, e nessuna delle due è la
    // fonte: i bonifici passano da Stripe Connect.

    public function test_le_coordinate_dello_step_undici_arrivano_al_profilo_partner(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->publishableDraftOf($partner);

        Livewire::test(HotelPayment::class)
            ->set('form.accountHolder', 'Mario Rossi')
            ->set('form.iban', 'IT60X0542811101000000123456')
            ->set('form.bic', 'UNCRITMM')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'account_holder' => 'Mario Rossi',
            'iban' => 'IT60X0542811101000000123456',
            'bic' => 'UNCRITMM',
        ]);
    }

    /** L'altro verso della stessa fonte unica: il profilo precompila lo step. */
    public function test_lo_step_undici_precompila_le_coordinate_gia_a_profilo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $partner->partnerProfile->update([
            'account_holder' => 'Mario Rossi',
            'iban' => 'IT60X0542811101000000123456',
            'bic' => 'UNCRITMM',
        ]);
        $this->publishableDraftOf($partner);

        Livewire::test(HotelPayment::class)
            ->assertSet('form.accountHolder', 'Mario Rossi')
            ->assertSet('form.iban', 'IT60X0542811101000000123456')
            ->assertSet('form.bic', 'UNCRITMM');
    }

    // ── Giro del tester, 28/09/2026: W7 dal wizard al profilo e ritorno ──────
    //
    // Le due prove W7 qui sopra partono da un profilo vuoto o da una bozza
    // vuota. Qui i casi in cui le due copie divergono, il giro completo con la
    // pagina del profilo, e che lo step non tocchi il resto del profilo.

    private const OLD_IBAN = 'IT60X0542811101000000123456';

    private const NEW_IBAN = 'IT02L1234512345123456789012';

    /**
     * Una richiesta nuova. Nel test l'utente autenticato è la STESSA istanza
     * fra un componente e l'altro, e tiene in cache il `partnerProfile`
     * caricato dal mount del primo: senza ricaricarlo il secondo leggerebbe il
     * profilo di prima. Nel browser ogni richiesta ricarica l'utente.
     */
    private function nextRequest(User $partner): void
    {
        $this->actingAs($partner->fresh());
    }

    public function test_se_profilo_e_bozza_divergono_lo_step_precompila_dal_profilo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $partner->partnerProfile->update(['account_holder' => 'Mario Rossi', 'iban' => self::NEW_IBAN, 'bic' => 'BCITITMM']);
        $this->publishableDraftOf($partner)->update(['account_holder' => 'Vecchio Titolare', 'iban' => self::OLD_IBAN, 'bic' => 'UNCRITMM']);

        Livewire::test(HotelPayment::class)
            ->assertSet('form.accountHolder', 'Mario Rossi')
            ->assertSet('form.iban', self::NEW_IBAN)
            ->assertSet('form.bic', 'BCITITMM');
    }

    /** Il giro completo: digitate allo step 11, le ritrova in Profilo → Metodo di pagamento senza riscriverle. */
    public function test_le_coordinate_dello_step_si_rileggono_nella_pagina_del_profilo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->publishableDraftOf($partner);

        Livewire::test(HotelPayment::class)
            ->set('form.accountHolder', 'Mario Rossi')
            ->set('form.iban', self::OLD_IBAN)
            ->set('form.bic', 'UNCRITMM')
            ->call('next')
            ->assertHasNoErrors();

        $this->nextRequest($partner);
        Livewire::test(PartnerProfilePayment::class)
            ->assertSet('form.accountHolder', 'Mario Rossi')
            ->assertSet('form.iban', self::OLD_IBAN)
            ->assertSet('form.bic', 'UNCRITMM');
    }

    /** E il ritorno: cambiate dal profilo, la struttura successiva le propone già aggiornate. */
    public function test_le_coordinate_cambiate_dal_profilo_arrivano_allo_step_della_struttura_dopo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $this->publishableDraftOf($partner);

        Livewire::test(HotelPayment::class)
            ->set('form.accountHolder', 'Mario Rossi')
            ->set('form.iban', self::OLD_IBAN)
            ->set('form.bic', 'UNCRITMM')
            ->call('next')
            ->assertHasNoErrors();

        $this->nextRequest($partner);
        Livewire::test(PartnerProfilePayment::class)
            ->set('form.iban', self::NEW_IBAN)
            ->call('save')
            ->assertHasNoErrors();

        // Seconda struttura: bozza nuova, in sessione.
        $this->publishableDraftOf($partner);

        $this->nextRequest($partner);
        Livewire::test(HotelPayment::class)
            ->assertSet('form.iban', self::NEW_IBAN)
            ->assertSet('form.accountHolder', 'Mario Rossi');
    }

    /** Uno step rifiutato non scrive niente, né sulla bozza né sul profilo. */
    public function test_uno_step_rifiutato_non_tocca_il_profilo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $partner->partnerProfile->update(['account_holder' => 'Mario Rossi', 'iban' => self::OLD_IBAN, 'bic' => 'UNCRITMM']);
        $this->publishableDraftOf($partner);

        Livewire::test(HotelPayment::class)
            ->set('form.iban', '')
            ->call('next')
            ->assertHasErrors(['form.iban']);

        $this->assertSame(self::OLD_IBAN, $partner->partnerProfile->fresh()->iban);
    }

    /**
     * `updateOrCreate([], ...)` col solo trio delle coordinate: il resto del
     * profilo (collegamento Stripe, ragione sociale, orari) non si tocca, e il
     * partner continua a pubblicare.
     */
    public function test_lo_step_non_tocca_il_resto_del_profilo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $partner->partnerProfile->update([
            'business_name' => 'Zampa Felice Srl',
            'opening_hours' => ['it' => 'Sempre aperto'],
        ]);
        $draft = $this->publishableDraftOf($partner);

        Livewire::test(HotelPayment::class)
            ->set('form.accountHolder', 'Mario Rossi')
            ->set('form.iban', self::OLD_IBAN)
            ->set('form.bic', 'UNCRITMM')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $profile = $partner->partnerProfile->fresh();
        $this->assertSame('Zampa Felice Srl', $profile->business_name);
        $this->assertSame('Sempre aperto', $profile->getTranslation('opening_hours', 'it'));
        $this->assertTrue($profile->canPublishFamily('struttura'));
        $this->assertTrue(Structure::withHidden()->where('structure_draft_id', $draft->id)->exists());
        $this->assertSame(1, $partner->partnerProfile()->count());
    }

    /**
     * Trovato dal tester il 28/09/2026 e chiuso lo stesso giorno (il dettaglio
     * legge ora il profilo, la bozza solo come ripiego), resto di W7. La correzione dichiara il profilo «la fonte» ma lascia le
     * coordinate anche sulla bozza «perché il dettaglio del servizio le
     * mostra»: la copia sulla bozza non segue più il profilo. Il partner cambia
     * l'IBAN in Profilo → Metodo di pagamento, apre il dettaglio della sua
     * struttura e ci legge ancora quello vecchio. Sono le «due copie che non si
     * sincronizzano» dell'audit, in un verso solo.
     */
    public function test_il_dettaglio_del_servizio_mostra_le_coordinate_del_profilo(): void
    {
        $partner = $this->actingAsPayablePartner();
        $draft = $this->publishableDraftOf($partner);

        Livewire::test(HotelPayment::class)
            ->set('form.accountHolder', 'Mario Rossi')
            ->set('form.iban', self::OLD_IBAN)
            ->set('form.bic', 'UNCRITMM')
            ->call('next')
            ->assertHasNoErrors();

        $this->nextRequest($partner);
        Livewire::test(PartnerProfilePayment::class)
            ->assertSet('form.iban', self::OLD_IBAN)
            ->set('form.iban', self::NEW_IBAN)
            ->call('save')
            ->assertHasNoErrors();

        $this->nextRequest($partner);
        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertDontSee(self::OLD_IBAN)
            ->assertSee(self::NEW_IBAN);
    }
}
