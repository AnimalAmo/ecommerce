<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Models\Region\Region;
use App\Models\Review\Review;
use App\Models\Structure\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Paginazione incrementale delle recensioni nei widget detail: parte da 3
 * (come da XD), "Carica altre recensioni" ne rivela altre 3 con clamp al
 * totale, e il bottone sparisce quando tutte sono mostrate (o se <= 3).
 */
class ReviewPaginationTest extends TestCase
{
    use RefreshDatabase;

    /** Struttura con slug noto nella regione lombardia, più $count recensioni. */
    private function structureWithReviews(int $count): Structure
    {
        Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);

        $structure = Structure::factory()->create(['slug' => 'hotel-test']);
        Review::factory()->count($count)->create(['reviewable_id' => $structure->id]);

        return $structure;
    }

    /** Servizio (type Service) con slug noto nella regione lombardia, più $count recensioni. */
    private function serviceWithReviews(int $count): Structure
    {
        Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);

        $service = Structure::factory()->service()->create(['slug' => 'dog-sitting-test']);
        Review::factory()->count($count)->create(['reviewable_id' => $service->id]);

        return $service;
    }

    public function test_structure_shows_three_reviews_and_the_button_when_more_exist(): void
    {
        $this->structureWithReviews(7);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-test'])
            ->assertOk()
            ->assertSet('reviewsShown', 3)
            ->assertViewHas('reviewsCount', 7)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 3)
            ->assertSee('Carica altre recensioni');
    }

    public function test_structure_load_more_reveals_the_next_batch_then_hides_the_button(): void
    {
        $this->structureWithReviews(7);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-test'])
            // Primo click: 3 → 6, restano recensioni → bottone ancora presente.
            ->call('loadMoreReviews')
            ->assertSet('reviewsShown', 6)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 6)
            ->assertSee('Carica altre recensioni')
            // Secondo click: 6 + 3 = 9 ma clamp a 7 (totale) → tutte mostrate, bottone via.
            ->call('loadMoreReviews')
            ->assertSet('reviewsShown', 7)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 7)
            ->assertDontSee('Carica altre recensioni');
    }

    public function test_structure_hides_the_button_with_exactly_three_reviews(): void
    {
        $this->structureWithReviews(3);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-test'])
            ->assertViewHas('reviewsCount', 3)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 3)
            ->assertDontSee('Carica altre recensioni');
    }

    public function test_structure_hides_the_button_with_fewer_than_three_reviews(): void
    {
        $this->structureWithReviews(2);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-test'])
            ->assertViewHas('reviewsCount', 2)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 2)
            ->assertDontSee('Carica altre recensioni');
    }

    public function test_service_paginates_reviews_incrementally(): void
    {
        $this->serviceWithReviews(5);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-test'])
            ->assertOk()
            ->assertSet('reviewsShown', 3)
            ->assertViewHas('reviewsCount', 5)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 3)
            ->assertSee('Carica altre recensioni')
            // 3 + 3 = 6 ma clamp a 5 → tutte mostrate, bottone via.
            ->call('loadMoreReviews')
            ->assertSet('reviewsShown', 5)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 5)
            ->assertDontSee('Carica altre recensioni');
    }

    public function test_service_hides_the_button_with_three_or_fewer_reviews(): void
    {
        $this->serviceWithReviews(3);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-test'])
            ->assertViewHas('reviewsCount', 3)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->count() === 3)
            ->assertDontSee('Carica altre recensioni');
    }
}
