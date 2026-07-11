<?php

namespace Tests\Feature\Partner\Publishing;

use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\StructureDraft;
use App\Models\User;
use App\Services\Partner\Publishing\DraftPublisher;
use Database\Seeders\AmenitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartboxPublisherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AmenitySeeder::class);
    }

    private function smartboxDraft(array $attributes = []): StructureDraft
    {
        return StructureDraft::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'status' => StructureDraft::STATUS_COMPLETED,
            'current_step' => 12,
            'service_category' => 'smartbox',
            'type' => 'benessere',
            'name' => ['it' => 'Weekend Zen col tuo cane', 'en' => 'Zen weekend with your dog'],
            'description' => ['it' => 'Relax e coccole per entrambi.'],
            'detailed_description' => ['it' => 'Due giorni di spa pet friendly.', 'en' => 'Two days of pet friendly spa.'],
            'duration_days' => 3,
            'cancellation_when' => '15',
            'meals' => ['colazione', 'cena'],
            'meal_times' => [
                'colazione' => ['from' => '08:00', 'to' => '10:30'],
                'pranzo' => ['from' => '', 'to' => ''],
                'cena' => ['from' => '19:30', 'to' => '21:30'],
            ],
            'included_services' => ['wifi', 'aria_condizionata'],
            'additional_services' => ['spa'],
            'animal_services' => ['omaggio'],
            'photos' => ['smartbox-photos/zen.jpg'],
            'price' => '215',
        ], $attributes));
    }

    public function test_publish_creates_the_catalog_package_from_the_draft(): void
    {
        $draft = $this->smartboxDraft();

        $package = app(DraftPublisher::class)->publish($draft);

        $this->assertInstanceOf(SmartboxPackage::class, $package);
        $this->assertSame('wellness', $package->type->value);
        $this->assertSame('weekend-zen-col-tuo-cane-'.$draft->id, $package->slug);
        $this->assertSame(21500, $package->price_cents);
        $this->assertSame(21500, $package->price_from_cents);
        // Regola piattaforma: validità 12 mesi; audience senza input wizard (v2).
        $this->assertSame(12, $package->validity_months);
        $this->assertSame('', $package->audience);
        $this->assertSame(15, $package->cancellation_policy_days);
        $this->assertSame('Weekend Zen col tuo cane', $package->getTranslation('title', 'it'));
        $this->assertSame('Two days of pet friendly spa.', $package->getTranslation('extended_description', 'en'));
        // Colonna NOT NULL senza fonte wizard: vuota ⇒ sezione nascosta.
        $this->assertSame([], $package->features);
        $this->assertSame('smartbox-photos/zen.jpg', $package->img);

        $icons = array_column($package->general_info, 'icon');
        $this->assertSame(['calendar-return', 'home', 'coffee', 'lunch'], $icons);
        $this->assertSame(['Soggiorno di 3 giorni'], $package->general_info[1]['lines']);
        // Solo cena selezionata: titolo singolo con orario.
        $this->assertSame('Cena inclusa', $package->general_info[3]['title']);
        $this->assertSame(['Orario: 19:30-21:30'], $package->general_info[3]['lines']);
    }

    public function test_publish_normalizes_decimal_prices_to_cents(): void
    {
        $package = app(DraftPublisher::class)->publish($this->smartboxDraft(['price' => '99.90']));

        $this->assertSame(9990, $package->price_cents);
    }

    public function test_publish_syncs_amenities_and_republishing_updates_the_row(): void
    {
        $publisher = app(DraftPublisher::class);
        $draft = $this->smartboxDraft();

        $first = $publisher->publish($draft);

        $hotel = collect($first->amenityRows('hotel'));
        $this->assertTrue($hotel->firstWhere('label', 'Wifi')['included']);
        $this->assertTrue($hotel->firstWhere('label', 'Aria condizionata negli spazi comuni')['included']);
        // 'spa' dagli additional → Spa inclusa.
        $this->assertTrue($hotel->firstWhere('label', 'Spa')['included']);
        $this->assertFalse($hotel->firstWhere('label', 'Pranzo')['included']);

        $draft->price = '250';
        $draft->save();
        $second = $publisher->publish($draft->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SmartboxPackage::count());
        $this->assertSame(25000, $second->price_cents);
    }
}
