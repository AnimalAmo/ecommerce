<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Smartbox\SmartboxPrice;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerSmartboxPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_price_field(): void
    {
        // Il wizard vive dentro il gruppo ['auth','partner']: da ospite è un redirect.
        $this->actingAsPayablePartner();

        $this->get(route('partner.smartbox.price'))
            ->assertOk()
            ->assertSee(__('partner.smartbox_price.heading'))
            ->assertSee(__('partner.smartbox_price.step'))
            ->assertSee(__('partner.smartbox_price.field_label'))
            ->assertSee(__('partner.smartbox_price.save'));
    }

    public function test_save_requires_a_price(): void
    {
        Livewire::test(SmartboxPrice::class)
            ->call('save')
            ->assertHasErrors('price');
    }

    public function test_save_completes_the_draft_and_redirects_to_dashboard(): void
    {
        // La bozza va a catalogo solo se il partner può essere pagato.
        $this->actingAsPayablePartner();

        Livewire::test(SmartboxPrice::class)
            ->set('price', '149,90')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));

        $draft = StructureDraft::first();
        // La virgola italiana viene normalizzata prima della validazione numeric.
        $this->assertSame('149.90', $draft->price);
        $this->assertSame('completed', $draft->status);
        $this->assertSame(12, $draft->current_step);
        $this->assertNull(session('structure_draft_id'));
    }

    public function test_save_rejects_a_non_numeric_price(): void
    {
        // '215 €' passava con la vecchia regola string ma rompeva il pricing B2C.
        Livewire::test(SmartboxPrice::class)
            ->set('price', '215 €')
            ->call('save')
            ->assertHasErrors('price');

        $this->assertNull(StructureDraft::first()?->price);
    }

    public function test_it_rehydrates_the_saved_price(): void
    {
        $draft = StructureDraft::create(['status' => 'draft', 'current_step' => 12, 'price' => '99']);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(SmartboxPrice::class)->assertSet('price', '99');
    }
}
