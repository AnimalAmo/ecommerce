<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\HotelPayment;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerHotelPaymentTest extends TestCase
{
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

    public function test_next_completes_to_the_dashboard_when_valid(): void
    {
        Livewire::test(HotelPayment::class)
            ->set('accountHolder', 'Mario Rossi')
            ->set('iban', 'IT60X0542811101000000123456')
            ->set('sdi', 'ABCDEF1')
            ->set('bic', 'UNCRITMM')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.dashboard'));
    }

    public function test_skip_completes_to_the_dashboard(): void
    {
        Livewire::test(HotelPayment::class)
            ->call('skip')
            ->assertRedirect(route('partner.dashboard'));
    }
}
