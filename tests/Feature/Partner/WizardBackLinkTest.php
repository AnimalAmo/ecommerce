<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityType;
use App\Livewire\Partner\MyServices\PartnerMyServices;
use App\Livewire\Partner\Smartbox\SmartboxType;
use App\Livewire\Partner\Structure\StructureType;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "Indietro" dal primo step di ogni famiglia. Portava sempre a "Crea
 * servizio", che apre una bozza nuova: chi stava modificando un servizio da
 * "I miei servizi" perdeva in silenzio la bozza, e continuando ne creava un
 * altro invece di modificare quello pubblicato.
 */
class WizardBackLinkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * href del pulsante "Indietro" dello step. L'header dell'area partner
     * linka sempre sia "Crea servizio" sia "I miei servizi": cercare l'href
     * in tutta la pagina darebbe ragione a qualunque pulsante.
     */
    private function backHref(string $html): ?string
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $buttons = (new DOMXPath($dom))->query('//main//a[@data-flux-button]');
        $this->assertSame(1, $buttons->length, 'Atteso un solo pulsante-link nello step: "Indietro".');

        return $buttons->item(0)->getAttribute('href');
    }

    /** @return array<string, array{0: class-string, 1: string, 2: string, 3: string}> */
    public static function families(): array
    {
        return [
            'struttura' => [StructureType::class, 'struttura', 'hotel', 'partner.structure.type'],
            'attivita' => [ActivityType::class, 'attivita', 'attivita', 'partner.activity.type'],
            'smartbox' => [SmartboxType::class, 'smartbox', 'soggiorno', 'partner.smartbox.type'],
        ];
    }

    private function serviceOf(User $partner, string $category, string $type, array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 11,
            'service_category' => $category,
            'type' => $type,
            'name' => ['it' => 'Servizio pubblicato'],
        ], $attributes));
    }

    #[DataProvider('families')]
    public function test_chi_modifica_un_servizio_torna_alla_lista(string $component, string $category, string $type, string $firstStep): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, $category, $type);

        Livewire::test(PartnerMyServices::class)
            ->call('edit', $draft->id)
            ->assertRedirect(route($firstStep));

        $this->assertSame(route('partner.services'), $this->backHref(Livewire::test($component)->html()));

        // Nessuna bozza nuova: la sessione punta ancora al servizio in modifica.
        $this->assertSame($draft->id, session('structure_draft_id'));
        $this->assertSame(1, StructureDraft::query()->where('user_id', $partner->id)->count());
    }

    #[DataProvider('families')]
    public function test_chi_modifica_una_bozza_in_attesa_torna_alla_lista(string $component, string $category, string $type, string $firstStep): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = $this->serviceOf($partner, $category, $type, [
            'status' => StructureDraft::STATUS_DRAFT,
            'publish_requested_at' => now(),
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->assertSame(route('partner.services'), $this->backHref(Livewire::test($component)->html()));
    }

    #[DataProvider('families')]
    public function test_un_servizio_nuovo_torna_alla_scelta_del_servizio(string $component, string $category, string $type, string $firstStep): void
    {
        $partner = $this->actingAsActivePartner();
        $draft = StructureDraft::create([
            'user_id' => $partner->id,
            'status' => StructureDraft::STATUS_DRAFT,
            'current_step' => 0,
            'service_category' => $category,
        ]);
        session(['structure_draft_id' => $draft->id]);

        $this->assertSame(route('partner.service.create'), $this->backHref(Livewire::test($component)->html()));
    }
}
