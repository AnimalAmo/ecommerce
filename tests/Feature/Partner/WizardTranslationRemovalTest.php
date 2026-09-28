<?php

namespace Tests\Feature\Partner;

use App\Livewire\Partner\Activity\ActivityIncluded;
use App\Livewire\Partner\Structure\HotelTitle;
use App\Models\Structure\StructureDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Difetto W5 (audit del 27/09/2026) FUORI dai quattro punti corretti dalla
 * lane nome il 28/09/2026: una traduzione inglese salvata una volta non si
 * toglie più. `array_filter(..., filled)` fa cadere la chiave della lingua
 * svuotata e `setTranslations()` di spatie non rimuove le lingue che non
 * riceve, quindi svuotando il tab EN la bozza tiene il testo di prima.
 *
 * Trovati dal tester il 28/09/2026 e chiusi lo stesso giorno: ogni punto
 * elencato sotto usa ora Translations::replacing. La lane nome li
 * ha elencati come file non assegnati; qui due rappresentanti, uno per il
 * percorso Struttura e uno DENTRO il wizard Attività che la correzione
 * dichiarava chiuso. Stesso difetto, stessa riga da cambiare
 * (Translations::replacing), anche in:
 *   - app/Livewire/Partner/Structure/HotelDescription.php:31
 *   - app/Livewire/Partner/Smartbox/SmartboxName.php:31
 *   - app/Livewire/Partner/Smartbox/SmartboxDescription.php:39-40
 *   - app/Livewire/Forms/HotelServicesForm.php:67
 *   - app/Livewire/Concerns/HandlesAnimalServicesStep.php:36
 *   - app/Services/Admin/Catalog/CatalogAdmin.php:305-310 e 331-332 (pannello)
 */
class WizardTranslationRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_svuotare_il_nome_inglese_di_un_hotel_lo_toglie(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 2,
            'service_category' => 'struttura',
            'type' => 'hotel',
            'name' => ['it' => 'Hotel Zampa Felice', 'en' => 'Happy Paw Hotel'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(HotelTitle::class)
            ->assertSet('name.en', 'Happy Paw Hotel')
            ->set('name.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame(
            ['it' => 'Hotel Zampa Felice'],
            $draft->fresh()->getTranslations('name'),
            'HotelTitle.php:31 salva ancora con array_filter: il nome inglese svuotato resta sulla bozza.',
        );
    }

    /** Stesso wizard delle quattro correzioni: il testo di «Altro» dei servizi aggiuntivi (step 6). */
    public function test_svuotare_linglese_dei_servizi_aggiuntivi_di_unattivita_lo_toglie(): void
    {
        $draft = StructureDraft::create([
            'status' => 'draft',
            'current_step' => 6,
            'service_category' => 'attivita',
            'type' => 'attivita',
            'additional_services' => ['altro'],
            'additional_other' => ['it' => 'Merenda per i cani', 'en' => 'Dog snack'],
        ]);
        session(['structure_draft_id' => $draft->id]);

        Livewire::test(ActivityIncluded::class)
            ->assertSet('form.additionalOther.en', 'Dog snack')
            ->set('form.additionalOther.en', '')
            ->call('next')
            ->assertHasNoErrors();

        $this->assertSame(
            ['it' => 'Merenda per i cani'],
            $draft->fresh()->getTranslations('additional_other'),
            'ActivityIncludedForm.php:67 salva ancora con array_filter: la traduzione inglese svuotata resta.',
        );
    }
}
