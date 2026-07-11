<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\MyServices\DeleteServiceModal;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteServiceModalTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $userId, string $name = 'Hotel Brescia'): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'name' => $name,
            'city' => 'Darfo Boario Terme',
            'province' => 'BS',
        ]);
    }

    public function test_open_loads_the_service_data(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id);

        Livewire::test(DeleteServiceModal::class)
            ->call('open', $draft->id)
            ->assertSet('serviceId', $draft->id)
            ->assertSet('name', 'Hotel Brescia')
            ->assertSet('location', 'Darfo Boario Terme (BS), Italia');
    }

    public function test_it_deletes_the_service_and_notifies_the_list(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id);

        Livewire::test(DeleteServiceModal::class)
            ->call('open', $draft->id)
            ->call('delete')
            ->assertDispatched('service-deleted');

        $this->assertDatabaseMissing('structure_drafts', ['id' => $draft->id]);
    }

    public function test_it_cannot_target_another_users_service(): void
    {
        $this->actingAsActivePartner();
        $othersDraft = $this->service(User::factory()->create()->id, 'Altrui Resort');

        Livewire::test(DeleteServiceModal::class)
            ->call('open', $othersDraft->id)
            ->assertSet('serviceId', null)
            ->call('delete');

        $this->assertDatabaseHas('structure_drafts', ['id' => $othersDraft->id]);
    }
}
