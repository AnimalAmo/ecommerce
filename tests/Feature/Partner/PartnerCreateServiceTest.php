<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\CreateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerCreateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_the_four_service_types(): void
    {
        $this->actingAsActivePartner();

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

    public function test_struttura_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'struttura')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.structure.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'struttura']);
    }

    public function test_attivita_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'attivita')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.activity.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'attivita']);
    }

    public function test_smartbox_saves_the_category_and_advances(): void
    {
        Livewire::test(CreateService::class)
            ->set('service', 'smartbox')
            ->call('next')
            ->assertHasNoErrors()
            ->assertRedirect(route('partner.smartbox.type'));

        $this->assertDatabaseHas('structure_drafts', ['service_category' => 'smartbox']);
    }
}
