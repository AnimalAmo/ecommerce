<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Il gate di approvazione dei publisher (config admin.moderation) e la sospensione che sopravvive alla ripubblicazione. */
class PublishingModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AmenitySeeder::class);
    }

    private function draft(): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => User::factory()->stripeConnected()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'type' => 'benessere',
            'name' => ['it' => 'Weekend Zen'],
            'description' => ['it' => 'Relax per entrambi.'],
            'duration_days' => 2,
            'photos' => ['smartbox-photos/zen.jpg'],
            'price' => '215',
        ]);
    }

    public function test_with_moderation_off_a_new_item_goes_live(): void
    {
        config(['admin.moderation' => false]);

        $package = app(DraftPublisher::class)->publish($this->draft());

        $this->assertTrue(SmartboxPackage::query()->whereKey($package->id)->exists());
    }

    public function test_with_moderation_on_a_new_item_waits_for_approval(): void
    {
        config(['admin.moderation' => true]);

        $package = app(DraftPublisher::class)->publish($this->draft());

        $fresh = SmartboxPackage::withHidden()->find($package->id);
        $this->assertSame('pending', $fresh->approval_status);
        $this->assertNotNull($fresh->approval_requested_at);
        $this->assertFalse(SmartboxPackage::query()->whereKey($package->id)->exists(), 'fuori dal sito');
    }

    public function test_an_approved_item_stays_live_when_the_partner_republishes(): void
    {
        $draft = $this->draft();
        $package = app(DraftPublisher::class)->publish($draft);

        config(['admin.moderation' => true]);
        app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertSame('approved', SmartboxPackage::withHidden()->find($package->id)->approval_status);
    }

    public function test_republishing_after_changes_requested_goes_back_to_pending(): void
    {
        config(['admin.moderation' => true]);
        $draft = $this->draft();
        $package = app(DraftPublisher::class)->publish($draft);
        $package->forceFill(['approval_status' => 'changes_requested', 'approval_note' => 'Foto sfocate'])->save();

        app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertSame('pending', SmartboxPackage::withHidden()->find($package->id)->approval_status);
    }

    public function test_a_suspended_item_stays_suspended_when_the_partner_republishes(): void
    {
        $draft = $this->draft();
        $package = app(DraftPublisher::class)->publish($draft);
        $package->forceFill(['suspended_at' => now()])->save();

        $again = app(DraftPublisher::class)->publish($draft->fresh());

        $this->assertSame($package->id, $again->id, 'stessa riga, nessun duplicato');
        $this->assertNotNull(SmartboxPackage::withHidden()->find($package->id)->suspended_at);
    }
}
