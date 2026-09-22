<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Structure\HotelTitle;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Ogni step del wizard scrive sulla bozza di `draftId` o della sessione.
 * Senza #[Locked] e senza controllo di proprietà, un partner poteva
 * compilare e pubblicare la bozza di un altro (IDOR); con l'impersonazione
 * dell'admin la sessione porterebbe la bozza di un partner nel wizard di un altro.
 */
class StructureDraftOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function draftOf(?int $userId, string $name): StructureDraft
    {
        return StructureDraft::create([
            'user_id' => $userId,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 2,
            'service_category' => 'struttura',
            'name' => ['it' => $name],
        ]);
    }

    public function test_l_id_della_bozza_non_si_riscrive_dal_client(): void
    {
        $this->actingAsActivePartner();
        $foreign = $this->draftOf(User::factory()->create()->id, 'Hotel altrui');

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(HotelTitle::class)->set('draftId', $foreign->id);
    }

    public function test_la_propria_bozza_in_sessione_si_riprende(): void
    {
        $partner = $this->actingAsActivePartner();
        $mine = $this->draftOf($partner->id, 'Hotel mio');
        session(['structure_draft_id' => $mine->id]);

        Livewire::test(HotelTitle::class)
            ->assertSet('draftId', $mine->id)
            ->assertSet('name', ['it' => 'Hotel mio', 'en' => '']);
    }

    public function test_una_bozza_altrui_in_sessione_non_si_apre(): void
    {
        Log::spy();
        $partner = $this->actingAsActivePartner();
        $foreign = $this->draftOf(User::factory()->create()->id, 'Hotel altrui');
        session(['structure_draft_id' => $foreign->id]);

        Livewire::test(HotelTitle::class)->assertSet('name', ['it' => '', 'en' => '']);

        $own = StructureDraft::query()->where('user_id', $partner->id)->sole();
        $this->assertSame($own->id, session('structure_draft_id'));
        $this->assertSame('Hotel altrui', $foreign->fresh()->getTranslation('name', 'it'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Bozza di un altro utente ignorata dal wizard partner', Mockery::on(
                fn (array $context): bool => $context['structure_draft_id'] === $foreign->id
                    && $context['user_id'] === $partner->id,
            ));
    }

    public function test_una_bozza_senza_proprietario_non_si_apre_da_partner(): void
    {
        // Bozze della finestra in cui il wizard era pubblico (prima dell'08/09/2026).
        $partner = $this->actingAsActivePartner();
        $orphan = $this->draftOf(null, 'Bozza da ospite');
        session(['structure_draft_id' => $orphan->id]);

        Livewire::test(HotelTitle::class)->assertSet('name', ['it' => '', 'en' => '']);

        $this->assertNotSame($orphan->id, session('structure_draft_id'));
        $this->assertNull($orphan->fresh()->user_id);
        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }
}
