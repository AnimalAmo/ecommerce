<?php

namespace Tests\Feature\Admin\Reviews;

use App\Livewire\Admin\Reviews\ReviewIndex;
use App\Models\Review\Review;
use App\Models\Structure\Structure;
use App\Models\User;
use App\Services\Admin\AdminCounters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReviewIndexTest extends TestCase
{
    use RefreshDatabase;

    /** Flux::toast non finisce nell'HTML: è un evento `toast-show` con il testo in slots.text. */
    private function toast(string $text): \Closure
    {
        return fn (string $name, array $params): bool => ($params['slots']['text'] ?? null) === $text;
    }

    public function test_a_customer_and_a_partner_are_refused(): void
    {
        Role::findOrCreate('client', 'web');
        $customer = User::factory()->create(['is_active' => true]);
        $customer->assignRole('client');

        $this->actingAs($customer)->get(route('admin.reviews'))->assertForbidden();

        $this->actingAsActivePartner();
        $this->get(route('admin.reviews'))->assertForbidden();
    }

    public function test_the_three_tabs_split_reviews_by_state(): void
    {
        $this->actingAsSuperadmin();
        $structure = Structure::factory()->create(['name' => 'Hotel Brescia']);

        Review::factory()->for($structure, 'reviewable')->pending()->create(['author_name' => 'Anna Persico', 'body' => 'Da moderare.']);
        Review::factory()->for($structure, 'reviewable')->create(['author_name' => 'Davide Corti', 'body' => 'Già online.']);
        Review::factory()->for($structure, 'reviewable')->create(['author_name' => 'Chiara Vialli']);
        Review::factory()->for($structure, 'reviewable')->hidden()->create(['author_name' => 'Utente Scortese']);

        $this->get(route('admin.reviews'))
            ->assertOk()
            ->assertSee('Recensioni')
            ->assertSee('Una recensione in attesa.')
            ->assertSee('Anna Persico')
            ->assertSee('su Hotel Brescia')
            ->assertSee('Da moderare.')
            ->assertDontSee('Davide Corti')
            ->assertDontSee('Utente Scortese');

        Livewire::test(ReviewIndex::class)
            ->set('tab', 'published')
            ->assertSee(['Davide Corti', 'Chiara Vialli', 'Già online.'])
            ->assertDontSee('Anna Persico')
            ->set('tab', 'hidden')
            ->assertSee('Utente Scortese')
            ->assertDontSee('Davide Corti');
    }

    public function test_an_unknown_tab_in_the_url_falls_back_to_pending(): void
    {
        $this->actingAsSuperadmin();
        Review::factory()->pending()->create(['author_name' => 'Anna Persico']);

        $this->get(route('admin.reviews', ['tab' => 'boh']))
            ->assertOk()
            ->assertSee('Anna Persico');
    }

    public function test_the_card_shows_score_flag_and_a_link_to_the_listing(): void
    {
        $this->actingAsSuperadmin();
        $structure = Structure::factory()->create(['name' => 'Villaggio Tre Capitelli']);
        Review::factory()->for($structure, 'reviewable')->flagged()->create(['rating' => 1.0, 'title' => 'Pessimo']);

        Livewire::test(ReviewIndex::class)
            ->set('tab', 'published')
            ->assertSee('1 / 5')
            ->assertSee('Segnalata')
            ->assertSee('Pessimo')
            ->assertSee(route('admin.catalog.show', ['type' => 'structure', 'id' => $structure->id]), escape: false);
    }

    public function test_search_narrows_by_author_title_or_text(): void
    {
        $this->actingAsSuperadmin();
        Review::factory()->create(['author_name' => 'Anna Persico', 'title' => 'Bellissimo', 'body' => 'Ciotole in camera.']);
        Review::factory()->create(['author_name' => 'Davide Corti', 'title' => 'Buono', 'body' => 'Area recintata piccola.']);

        Livewire::test(ReviewIndex::class)
            ->set('tab', 'published')
            ->set('q', 'recintata')
            ->assertSee('Davide Corti')
            ->assertDontSee('Anna Persico')
            ->set('q', 'persico')
            ->assertSee('Anna Persico')
            ->assertDontSee('Davide Corti');
    }

    public function test_publish_a_pending_review(): void
    {
        $this->actingAsSuperadmin();
        $structure = Structure::factory()->create(['rating' => null]);
        $review = Review::factory()->for($structure, 'reviewable')->pending()->create(['rating' => 4.0]);

        Livewire::test(ReviewIndex::class)
            ->assertSee('Pubblica')
            ->call('publish', $review->id)
            ->assertDispatched('toast-show', $this->toast('Recensione pubblicata.'));

        $review = Review::withHidden()->find($review->id);
        $this->assertSame(Review::STATUS_PUBLISHED, $review->status);
        $this->assertNotNull($review->moderated_at);
        $this->assertSame(4.0, $structure->fresh()->rating);
    }

    public function test_hide_a_published_review_and_restore_it(): void
    {
        $this->actingAsSuperadmin();
        $structure = Structure::factory()->create(['rating' => 3.0]);
        Review::factory()->for($structure, 'reviewable')->create(['rating' => 5.0]);
        $review = Review::factory()->for($structure, 'reviewable')->create(['rating' => 1.0]);

        $component = Livewire::test(ReviewIndex::class)
            ->set('tab', 'published')
            ->call('hide', $review->id)
            ->assertDispatched('toast-show', $this->toast('Recensione nascosta: non si vede più sul sito.'));

        $this->assertSame(Review::STATUS_HIDDEN, Review::withHidden()->find($review->id)->status);
        $this->assertSame(5.0, $structure->fresh()->rating);

        $component->set('tab', 'hidden')
            ->assertSee('Ripristina')
            ->call('publish', $review->id);

        $this->assertSame(Review::STATUS_PUBLISHED, Review::withHidden()->find($review->id)->status);
        $this->assertSame(3.0, $structure->fresh()->rating);
    }

    public function test_delete_asks_first_and_then_removes_the_review(): void
    {
        $this->actingAsSuperadmin();
        $structure = Structure::factory()->create(['rating' => 3.0]);
        Review::factory()->for($structure, 'reviewable')->create(['rating' => 5.0]);
        $review = Review::factory()->for($structure, 'reviewable')->hidden()->create(['author_name' => 'Utente Scortese', 'rating' => 1.0]);

        Livewire::test(ReviewIndex::class)
            ->set('tab', 'hidden')
            ->call('askDelete', $review->id)
            ->assertSee('Eliminare questa recensione?')
            ->assertSee('La recensione di Utente Scortese verrà rimossa definitivamente.')
            ->call('delete')
            ->assertDispatched('toast-show', $this->toast('Recensione eliminata.'));

        $this->assertNull(Review::withHidden()->find($review->id));
        $this->assertSame(5.0, $structure->fresh()->rating);
    }

    public function test_write_to_the_author_only_when_the_review_has_a_live_account(): void
    {
        $this->actingAsSuperadmin();
        $author = User::factory()->create(['email' => 'anna@example.com']);
        $review = Review::factory()->pending()->create(['author_name' => 'Anna Persico']);
        $review->forceFill(['user_id' => $author->id])->save();
        Review::factory()->pending()->create(['author_name' => 'Recensione seminata']);

        Livewire::test(ReviewIndex::class)
            ->assertSee('mailto:anna@example.com', escape: false)
            ->assertSee('Scrivi all&#039;autore', escape: false);

        $author->forceFill(['anonymized_at' => now(), 'email' => 'anonimo-1@anonimizzato.invalid'])->save();

        Livewire::test(ReviewIndex::class)
            ->assertDontSee('mailto:', escape: false);
    }

    public function test_a_review_whose_listing_is_gone_still_shows(): void
    {
        $this->actingAsSuperadmin();
        $review = Review::factory()->create(['author_name' => 'Orfana']);
        Structure::query()->whereKey($review->reviewable_id)->delete();

        Livewire::test(ReviewIndex::class)
            ->set('tab', 'published')
            ->assertSee('Orfana')
            ->assertSee('scheda rimossa');
    }

    public function test_the_navigation_badge_counts_pending_reviews(): void
    {
        Review::factory()->count(2)->pending()->create();
        Review::factory()->create();
        Review::factory()->hidden()->create();

        $this->assertSame(2, app(AdminCounters::class)->pendingReviews());
    }
}
