<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\CreateService;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerCreateServiceTest extends TestCase
{
    public function test_page_renders_the_four_service_types(): void
    {
        $this->get(route('partner.service.create'))
            ->assertOk()
            ->assertSee(__('partner.create_service.heading'))
            ->assertSee(__('partner.create_service.helper'))
            ->assertSee(__('partner.create_service.struttura_title'))
            ->assertSee(__('partner.create_service.attivita_title'))
            ->assertSee(__('partner.create_service.servizi_title'))
            ->assertSee(__('partner.create_service.smartbox_title'))
            ->assertSee(__('partner.create_service.next'));
    }

    public function test_next_requires_a_service(): void
    {
        Livewire::test(CreateService::class)
            ->call('next')
            ->assertHasErrors('service');
    }

    public function test_next_rejects_an_unknown_service(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'inesistente')
            ->call('next')
            ->assertHasErrors('service');
    }

    public function test_next_accepts_smartbox(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'smartbox')
            ->call('next')
            ->assertHasNoErrors();
    }
}
