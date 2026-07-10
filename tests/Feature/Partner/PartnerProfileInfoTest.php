<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Profile\PartnerProfileInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerProfileInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_personal_and_fiscal_fields(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.profile'))
            ->assertOk()
            ->assertSee(__('partner.profile.info_heading'))
            ->assertSee(__('partner.profile.business_name'))
            ->assertSee(__('partner.profile.vat'))
            ->assertSee(__('partner.profile.pec'))
            ->assertSee(__('partner.profile.save'));
    }

    public function test_it_rehydrates_the_partner_data(): void
    {
        $partner = $this->actingAsActivePartner(['first_name' => 'Susanna', 'last_name' => 'Rossi']);
        $partner->partnerProfile()->create(['business_name' => 'Hotel Rosovino', 'vat' => '86334519757']);

        Livewire::test(PartnerProfileInfo::class)
            ->assertSet('form.firstName', 'Susanna')
            ->assertSet('form.businessName', 'Hotel Rosovino')
            ->assertSet('form.vat', '86334519757');
    }

    public function test_save_requires_the_mandatory_fields(): void
    {
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.firstName', '')
            ->set('form.businessName', '')
            ->set('form.vat', '')
            ->call('save')
            ->assertHasErrors(['form.firstName', 'form.businessName', 'form.vat']);
    }

    public function test_save_rejects_an_email_already_in_use(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.email', 'taken@example.com')
            ->call('save')
            ->assertHasErrors(['form.email']);
    }

    public function test_save_persists_personal_and_fiscal_data(): void
    {
        $partner = $this->actingAsActivePartner();

        Livewire::test(PartnerProfileInfo::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.city', 'Padova')
            ->set('form.zip', '35100')
            ->set('form.vat', '12345678901')
            ->set('form.phone', '3331234567')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->set('form.pec', 'pethotel@pec.it')
            ->set('form.sdi', 'ABCDEF1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $partner->id,
            'first_name' => 'Mario',
            'email' => 'mario@example.com',
        ]);
        $this->assertDatabaseHas('partner_profiles', [
            'user_id' => $partner->id,
            'business_name' => 'Pet Hotel Srl',
            'vat' => '12345678901',
            'city' => 'Padova',
        ]);
    }
}
