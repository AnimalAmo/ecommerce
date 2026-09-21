<?php

namespace Tests\Feature\Content;

use App\Livewire\Content\Community;
use App\Livewire\Content\PostDetail;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReply;
use App\Models\User;
use App\Services\Admin\AdminCounters;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Il lato pubblico della moderazione: "Segnala" alimenta la coda del
 * pannello, e quello che il pannello nasconde sparisce dal sito.
 */
class CommunityReportTest extends TestCase
{
    use RefreshDatabase;

    private function communityPost(array $attributes = []): CommunityPost
    {
        return CommunityPost::factory()->create(array_merge([
            'title' => 'Weekend sul Garda con Nina',
            'body' => 'Trovato un sentiero all\'ombra perfetto.',
        ], $attributes));
    }

    public function test_a_logged_in_user_reports_a_post_once(): void
    {
        $post = $this->communityPost();
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Community::class)
            ->assertSee(__('community.report'))
            ->call('report', $post->id);

        Livewire::test(PostDetail::class, ['post' => $post->id])
            ->assertSee(__('community.reported_badge'))
            ->call('report');

        $post->refresh();
        $this->assertSame(1, $post->reports_count);
        $this->assertDatabaseHas('community_post_reports', ['community_post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertDatabaseCount('community_post_reports', 1);
    }

    public function test_reports_from_different_people_add_up_and_feed_the_panel_counter(): void
    {
        $post = $this->communityPost();

        foreach (User::factory()->count(3)->create() as $user) {
            $this->actingAs($user);
            Livewire::test(PostDetail::class, ['post' => $post->id])->call('report');
        }

        $this->assertSame(3, $post->refresh()->reports_count);
        $this->assertSame(1, (new AdminCounters)->flaggedPosts());
    }

    public function test_a_guest_is_asked_to_log_in(): void
    {
        $post = $this->communityPost();

        Livewire::test(Community::class)
            ->call('report', $post->id)
            ->assertDispatched('modal-show', name: 'login');

        $this->assertSame(0, $post->refresh()->reports_count);
    }

    public function test_the_author_cannot_report_their_own_post(): void
    {
        $author = User::factory()->create();
        $post = $this->communityPost(['user_id' => $author->id]);
        $this->actingAs($author);

        Livewire::test(PostDetail::class, ['post' => $post->id])
            ->assertDontSee(__('community.report'))
            ->call('report');

        $this->assertSame(0, $post->refresh()->reports_count);
    }

    public function test_a_hidden_post_disappears_everywhere_on_the_site(): void
    {
        $this->communityPost(['title' => 'Promemoria sul trasportino', 'body' => 'In auto il trasportino va fissato.']);
        // Il più recente: senza il filtro sarebbe lui il post della fascia in home.
        $post = $this->communityPost(['hidden_at' => now()]);

        Livewire::test(Community::class)
            ->assertDontSee('Weekend sul Garda con Nina')
            ->assertSee('Promemoria sul trasportino');

        $this->get(route('community.post', $post->id))->assertNotFound();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('In auto il trasportino va fissato.')
            ->assertDontSee('Trovato un sentiero all&#039;ombra perfetto.', false);
    }

    public function test_a_hidden_post_cannot_be_answered_or_reported(): void
    {
        $post = $this->communityPost(['hidden_at' => now()]);
        $this->actingAs(User::factory()->create());

        // Nel browser una ModelNotFoundException è un 404; qui arriva com'è.
        foreach (['reply', 'report'] as $action) {
            try {
                Livewire::test(Community::class)
                    ->set("replyDrafts.{$post->id}", 'Risposta a un post rimosso')
                    ->call($action, $post->id);

                $this->fail("{$action} su un post rimosso doveva fallire");
            } catch (ModelNotFoundException) {
                // atteso
            }
        }

        $this->assertDatabaseCount('community_post_replies', 0);
        $this->assertDatabaseCount('community_post_reports', 0);
    }

    public function test_a_hidden_reply_disappears_but_the_post_stays(): void
    {
        $post = $this->communityPost();
        CommunityPostReply::factory()->for($post, 'post')->create(['body' => 'Risposta visibile']);
        CommunityPostReply::factory()->for($post, 'post')->create(['body' => 'Risposta offensiva', 'hidden_at' => now()]);

        Livewire::test(PostDetail::class, ['post' => $post->id])
            ->assertSee('Risposta visibile')
            ->assertDontSee('Risposta offensiva');

        Livewire::test(Community::class)
            ->assertSee('Risposta visibile')
            ->assertDontSee('Risposta offensiva');
    }
}
