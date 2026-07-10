<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelPayment;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_payment_fields(): void
    {
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
            ->assertHasErrors(['accountHolder', 'iban', 'sdi', 'bic']);
    }

    public function test_next_saves_and_completes_to_the_dashboard(): void
    {
        Livewire::test(HotelPayment::class)
            ->set('accountHolder', 'Mario Rossi')
            ->set('iban', 'IT60X0542811101000000123456')
            ->set('sdi', 'ABCDEF1')
            ->set('bic', 'UNCRITMM')
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
        Livewire::test(HotelPayment::class)
            ->call('skip')
            ->assertRedirect(route('partner.dashboard'));

        $this->assertDatabaseHas('structure_drafts', ['status' => 'completed']);
    }

    public function test_it_rehydrates_the_saved_iban(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 11, 'iban' => 'IT99']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelPayment::class)->assertSet('iban', 'IT99');
    }
}
