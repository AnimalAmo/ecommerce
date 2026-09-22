<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelPayment;
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
}
