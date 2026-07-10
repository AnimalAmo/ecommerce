<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\PartnerRegisterStep1;
use App\Livewire\Partner\PartnerRegisterStep2;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerRegistrationTest extends TestCase
{
    public function test_step_1_page_renders(): void
    {
        $this->get(route('partner.register'))
            ->assertOk()
            ->assertSee(__('partner.register.heading'))
            ->assertSee(__('partner.register.step'))
            ->assertSee(__('partner.register.next'));
    }

    public function test_step_1_requires_the_mandatory_fields(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->call('submit')
            ->assertHasErrors(['form.firstName', 'form.email', 'form.vat', 'form.pec', 'form.sdi'])
            ->assertNoRedirect();
    }

    public function test_step_1_rejects_a_non_numeric_cap(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.zip', 'abc')
            ->call('submit')
            ->assertHasErrors(['form.zip']);
    }

    public function test_step_1_advances_to_step_2_when_valid(): void
    {
        Livewire::test(PartnerRegisterStep1::class)
            ->set('form.firstName', 'Mario')
            ->set('form.lastName', 'Rossi')
            ->set('form.businessName', 'Pet Hotel Srl')
            ->set('form.email', 'mario@example.com')
            ->set('form.address', 'Via Roma 1')
            ->set('form.province', 'PD')
            ->set('form.zip', '35100')
            ->set('form.phone', '3331234567')
            ->set('form.vat', '12345678901')
            ->set('form.taxCode', 'RSSMRA80A01H501U')
            ->set('form.pec', 'pethotel@pec.it')
            ->set('form.sdi', 'ABCDEF1')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.register.step2'));
    }

    public function test_step_2_page_renders_the_three_service_options(): void
    {
        $this->get(route('partner.register.step2'))
            ->assertOk()
            ->assertSee(__('partner.register2.step'))
            ->assertSee(__('partner.register2.struttura_title'))
            ->assertSee(__('partner.register2.attivita_title'))
            ->assertSee(__('partner.register2.servizi_title'))
            ->assertSee(__('partner.register2.submit'));
    }

    public function test_step_2_requires_a_service(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->call('createAccount')
            ->assertHasErrors('service');
    }

    public function test_step_2_rejects_an_unknown_service(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'qualcosaltro')
            ->call('createAccount')
            ->assertHasErrors('service');
    }

    public function test_step_2_accepts_a_valid_selection(): void
    {
        Livewire::test(PartnerRegisterStep2::class)
            ->set('service', 'attivita')
            ->call('createAccount')
            ->assertHasNoErrors();
    }
}
