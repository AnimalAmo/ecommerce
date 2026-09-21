<?php

namespace Tests\Feature\Admin\Reviews;

use App\Livewire\Catalog\AnimalHolidayRegion;
use App\Livewire\Catalog\AnimalHolidayService;
use App\Livewire\Catalog\AnimalHolidayStructure;
use App\Models\Favorite\Favorite;
use App\Models\Region\Region;
use App\Models\Review\Review;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\Reviews\ReviewModeration;
use App\Services\FavoriteService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sul sito arrivano solo le recensioni pubblicate: una nascosta dal pannello
 * o ancora in attesa non si legge, non si conta e non pesa sulla media, in
 * tutti i punti che leggono le recensioni (scheda struttura, scheda servizio,
 * card della regione, card dei preferiti).
 */
class ReviewVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Region $region;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create(['slug' => 'lombardia', 'name' => 'Lombardia']);
    }

    /** Una pubblicata da 5, una nascosta da 1, una in attesa da 1: media sul sito 5. */
    private function withMixedReviews(Structure $structure): Review
    {
        Review::factory()->for($structure, 'reviewable')->create(['title' => 'Titolo pubblicato', 'rating' => 5.0]);
        Review::factory()->for($structure, 'reviewable')->pending()->create(['title' => 'Titolo in moderazione', 'rating' => 1.0]);

        return Review::factory()->for($structure, 'reviewable')->create(['title' => 'Titolo da nascondere', 'rating' => 1.0]);
    }

    public function test_the_default_query_sees_only_published_reviews(): void
    {
        Review::factory()->create();
        Review::factory()->pending()->create();
        Review::factory()->hidden()->create();

        $this->assertSame(1, Review::query()->count());
        $this->assertSame(3, Review::withHidden()->count());
    }

    public function test_the_structure_page_drops_hidden_and_pending_reviews_from_list_count_and_average(): void
    {
        $structure = Structure::factory()->create(['slug' => 'hotel-test', 'region_id' => $this->region->id, 'rating' => 3.0]);
        $review = $this->withMixedReviews($structure);

        app(ReviewModeration::class)->hide($review);

        $this->assertSame(5.0, $structure->fresh()->rating);

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-test'])
            ->assertViewHas('reviewsCount', 1)
            ->assertSee('Titolo pubblicato')
            ->assertDontSee('Titolo da nascondere')
            ->assertDontSee('Titolo in moderazione')
            ->assertSee('5 (1 recensioni)');
    }

    public function test_load_more_counts_only_published_reviews(): void
    {
        $structure = Structure::factory()->create(['slug' => 'hotel-test', 'region_id' => $this->region->id]);
        Review::factory()->count(4)->for($structure, 'reviewable')->create();
        Review::factory()->count(3)->for($structure, 'reviewable')->hidden()->create();

        Livewire::test(AnimalHolidayStructure::class, ['region' => 'lombardia', 'structure' => 'hotel-test'])
            ->call('loadMoreReviews')
            ->assertSet('reviewsShown', 4)
            ->assertDontSee('Carica altre recensioni');
    }

    public function test_the_service_page_drops_hidden_and_pending_reviews(): void
    {
        $service = Structure::factory()->service()->create(['slug' => 'dog-sitting-test', 'region_id' => $this->region->id, 'rating' => 3.0]);
        $review = $this->withMixedReviews($service);

        app(ReviewModeration::class)->hide($review);

        Livewire::test(AnimalHolidayService::class, ['region' => 'lombardia', 'service' => 'dog-sitting-test'])
            ->assertViewHas('reviewsCount', 1)
            ->assertSee('Titolo pubblicato')
            ->assertDontSee('Titolo da nascondere')
            ->assertDontSee('Titolo in moderazione')
            ->assertSee('5 stelle');
    }

    public function test_a_region_card_with_only_hidden_reviews_shows_new_instead_of_a_vote(): void
    {
        $structure = Structure::factory()->create(['name' => 'Hotel Nascosto', 'region_id' => $this->region->id, 'rating' => 4.0]);
        $review = Review::factory()->for($structure, 'reviewable')->create(['rating' => 4.0]);

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->assertSee('Hotel Nascosto')
            ->assertDontSee('Nuovo');

        app(ReviewModeration::class)->hide($review);

        $this->assertNull($structure->fresh()->rating);

        Livewire::test(AnimalHolidayRegion::class, ['region' => 'lombardia'])
            ->assertSee('Hotel Nascosto')
            ->assertSee('Nuovo');
    }

    public function test_the_favorites_card_loses_the_vote_of_a_hidden_review(): void
    {
        $user = User::factory()->create();
        $structure = Structure::factory()->create(['rating' => 4.0]);
        $review = Review::factory()->for($structure, 'reviewable')->create(['rating' => 4.0]);
        Favorite::factory()->for($user)->create(['favoritable_type' => 'structure', 'favoritable_id' => $structure->id]);

        app(ReviewModeration::class)->hide($review);

        $card = app(FavoriteService::class)->cards($user)[0];
        $this->assertSame('rating', $card['metaType']);
        $this->assertSame('Nuovo', $card['metaText']);
    }

    public function test_restoring_a_review_puts_it_back_in_the_average(): void
    {
        $structure = Structure::factory()->create(['rating' => 5.0]);
        Review::factory()->for($structure, 'reviewable')->create(['rating' => 5.0]);
        $hidden = Review::factory()->for($structure, 'reviewable')->hidden()->create(['rating' => 4.0]);

        app(ReviewModeration::class)->publish($hidden);

        $this->assertSame(4.5, $structure->fresh()->rating);
        $this->assertSame(2, $structure->reviews()->count());
    }

    public function test_reseeding_keeps_a_hidden_demo_review_hidden_and_does_not_duplicate_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $review = Structure::query()->where('slug', 'hotel-brescia')->firstOrFail()->reviews()->firstOrFail();
        app(ReviewModeration::class)->hide($review);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(12 * 12, Review::withHidden()->count());
        $this->assertSame(Review::STATUS_HIDDEN, Review::withHidden()->find($review->id)->status);
    }
}
