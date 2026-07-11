<?php

namespace Tests\Feature\Partner;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerBookingDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away(): void
    {
        $this->get(route('partner.bookings.show', 'BD94KEU9E'))->assertRedirect();
    }

    public function test_page_renders_customer_and_booking_info(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.bookings.show', 'BD94KEU9E'))
            ->assertOk()
            ->assertSee(__('partner.bookings.detail_customer'))
            ->assertSee(__('partner.bookings.detail_booking'))
            ->assertSee(__('partner.bookings.detail_print'))
            ->assertSee('BD94KEU9E')
            ->assertSee('Giulia')
            ->assertSee('giulia.rossi@gmail.com')
            ->assertSee('Hotel Brescia')
            ->assertSee('Bonifico bancario')
            ->assertSee('135 €')
            ->assertSee('booking-detail-hotel.jpg');
    }

    public function test_the_list_links_each_row_to_the_detail(): void
    {
        $this->actingAsActivePartner();

        $this->get(route('partner.bookings'))
            ->assertOk()
            ->assertSee(route('partner.bookings.show', 'BD94KEU9E'));
    }
}
