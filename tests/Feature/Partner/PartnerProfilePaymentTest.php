<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfilePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerProfilePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_payment_fields(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile.payment'))
            ->assertOk()
            ->assertSee(__('partner.profile.payment_heading'))
            ->assertSee(__('partner.profile.account_holder'))
            ->assertSee(__('partner.profile.iban'))
            ->assertSee(__('partner.profile.bic'));
    }

    public function test_it_rehydrates_the_saved_payment_data(): void
    {
        $partner = $this->actingAsActivePartner();
        $partner->partnerProfile()->create(['iban' => 'IT037400000000007382', 'account_holder' => 'Susanna Rossi']);

        Livewire::test(PartnerProfilePayment::class)
            ->assertSet('form.iban', 'IT037400000000007382')
            ->assertSet('form.accountHolder', 'Susanna Rossi');
    }

    public function test_save_requires_the_fields(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->call('save')
            ->assertHasErrors(['form.accountHolder', 'form.iban', 'form.sdi', 'form.bic']);
    }

    public function test_save_persists_the_payment_data(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(PartnerProfilePayment::class)
            ->set('form.accountHolder', 'Susanna Rossi')
            ->set('form.iban', 'IT60X0542811101000000123456')
            ->set('form.sdi', 'ABCDEF1')
            ->set('form.bic', 'UNCRITMM')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'account_holder' => 'Susanna Rossi',
            'iban' => 'IT60X0542811101000000123456',
        ]);
    }
}
