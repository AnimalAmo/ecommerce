<?php

namespace Tests\Feature\Partner;

use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerServiceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $userId, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'type' => 'hotel',
            'service_category' => 'struttura',
            'name' => 'Hotel Brescia',
            'city' => 'Darfo Boario Terme',
            'province' => 'BS',
            'description' => 'Hotel pet-friendly.',
        ], $attributes));
    }

    public function test_owner_sees_the_service_detail_sections(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee('Hotel Brescia')
            ->assertSee(__('partner.services.section_type'))
            ->assertSee(__('partner.services.section_rooms'))
            ->assertSee(__('partner.services.section_payment'))
            ->assertSee('Hotel pet-friendly.');
    }

    public function test_option_keys_render_as_localized_labels(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, [
            'services' => ['wifi', 'piscina'],
            'rules' => ['vietato_fumare'],
            'animal_services' => ['area_animali'],
        ]);

        $this->get(route('partner.services.show', $draft))
            ->assertOk()
            ->assertSee(__('partner.hotel_services.svc_wifi'))       // "Wi-fi gratuito", non "wifi"
            ->assertSee(__('partner.hotel_services.svc_pool'))
            ->assertSee(__('partner.hotel_services.rule_no_smoking'))
            ->assertSee(__('partner.hotel_animal_services.opt_area'))
            ->assertSee(__('partner.structure_type.hotel'));
    }

    public function test_another_users_service_is_forbidden(): void
    {
        $this->actingAsActivePartner();
        $othersDraft = $this->service(User::factory()->create()->id);

        $this->get(route('partner.services.show', $othersDraft))->assertForbidden();
    }

    public function test_a_non_completed_draft_is_forbidden(): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->service($partner->id, ['status' => StructureDraft::STATUS_DRAFT]);

        $this->get(route('partner.services.show', $draft))->assertForbidden();
    }
}
