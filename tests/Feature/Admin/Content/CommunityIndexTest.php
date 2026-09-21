<?php

namespace Tests\Feature\Admin\Content;

use App\Livewire\Admin\Content\CommunityIndex;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReply;
use App\Models\User;
use App\Support\Admin\AdminNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsSuperadmin();
    }

    private function communityPost(string $author, array $attributes = []): CommunityPost
    {
        $post = CommunityPost::factory()->create(array_merge([
            'author_name' => $author,
            'title' => 'Post di '.$author,
            'body' => 'Testo di '.$author.'.',
        ], $attributes));

        // reports_count / hidden_at non sono fillable: si scrivono come fa il pannello.
        $post->forceFill(array_intersect_key($attributes, array_flip(['reports_count', 'hidden_at'])))->save();

        return $post;
    }

    public function test_the_flagged_queue_is_the_default_view(): void
    {
        $this->communityPost('Chiara Vialli');
        $this->communityPost('Utente anonimo', ['reports_count' => 4]);
        $this->communityPost('Davide Corti', ['reports_count' => 2]);
        $this->communityPost('Marta Belloni', ['hidden_at' => now()]);

        $this->get(route('admin.community'))
            ->assertOk()
            ->assertSee('I post pubblicati dagli utenti. 2 sono stati segnalati.')
            ->assertSeeInOrder(['Utente anonimo', '4 segnalazioni', 'Davide Corti', '2 segnalazioni'])
            ->assertDontSee('Chiara Vialli')
            ->assertDontSee('Marta Belloni');

        $this->assertSame(['flagged' => 2, 'all' => 3, 'hidden' => 1], Livewire::test(CommunityIndex::class)->viewData('counts'));
    }

    public function test_the_other_tabs_list_published_and_removed_posts(): void
    {
        $this->communityPost('Chiara Vialli');
        $this->communityPost('Marta Belloni', ['hidden_at' => now()]);

        Livewire::test(CommunityIndex::class)
            ->set('tab', 'all')
            ->assertSee('Chiara Vialli')
            ->assertDontSee('Marta Belloni')
            ->set('tab', 'hidden')
            ->assertSee('Marta Belloni')
            ->assertSee('Ripristina')
            ->assertDontSee('Chiara Vialli');
    }

    public function test_keeping_a_post_empties_its_reports_and_the_badge(): void
    {
        $post = $this->communityPost('Davide Corti', ['reports_count' => 2]);

        Livewire::test(CommunityIndex::class)
            ->call('keep', $post->id)
            ->assertSee(__('admin-content.community.empty.flagged'));

        $post->refresh();
        $this->assertSame(0, $post->reports_count);
        $this->assertNotNull($post->moderated_at);
        $this->assertNull($post->hidden_at);
        $this->get(route('community.post', $post->id))->assertOk();
    }

    public function test_removing_asks_first_and_takes_the_post_off_the_site(): void
    {
        $post = $this->communityPost('Utente anonimo', ['reports_count' => 4]);

        Livewire::test(CommunityIndex::class)
            ->call('ask', 'hide', $post->id)
            ->assertSee('Rimuovere questo post?')
            ->assertSee('Il post di Utente anonimo sparisce dalla community')
            ->call('confirm')
            ->assertSet('confirming', null);

        $post->refresh();
        $this->assertNotNull($post->hidden_at);
        $this->assertSame(0, $post->reports_count, 'una volta deciso, non resta nella coda');
        $this->get(route('community.post', $post->id))->assertNotFound();
    }

    public function test_a_removed_post_can_be_restored(): void
    {
        $post = $this->communityPost('Marta Belloni', ['hidden_at' => now()]);

        Livewire::test(CommunityIndex::class)
            ->set('tab', 'hidden')
            ->call('restore', $post->id);

        $this->assertNull($post->refresh()->hidden_at);
        $this->get(route('community.post', $post->id))->assertOk();
    }

    public function test_deleting_removes_the_post_its_replies_and_its_reports(): void
    {
        $post = $this->communityPost('Marta Belloni', ['hidden_at' => now()]);
        CommunityPostReply::factory()->for($post, 'post')->count(2)->create();
        $post->reports()->create(['user_id' => User::factory()->create()->id]);

        Livewire::test(CommunityIndex::class)
            ->set('tab', 'hidden')
            ->call('ask', 'delete', $post->id)
            ->assertSee('Eliminare definitivamente?')
            ->call('confirm');

        $this->assertModelMissing($post);
        $this->assertDatabaseCount('community_post_replies', 0);
        $this->assertDatabaseCount('community_post_reports', 0);
    }

    public function test_replies_are_hidden_restored_and_deleted_one_by_one(): void
    {
        $post = $this->communityPost('Chiara Vialli');
        $kept = CommunityPostReply::factory()->for($post, 'post')->create(['author_name' => 'Luca', 'body' => 'Risposta gentile']);
        $bad = CommunityPostReply::factory()->for($post, 'post')->create(['author_name' => 'Troll', 'body' => 'Risposta offensiva']);

        $component = Livewire::test(CommunityIndex::class)
            ->set('tab', 'all')
            ->assertSee('2 risposte')
            ->call('openReplies', $post->id)
            ->assertSee('Risposta gentile')
            ->assertSee('Risposta offensiva')
            ->call('hideReply', $bad->id);

        $this->assertNotNull($bad->refresh()->hidden_at);
        $this->get(route('community.post', $post->id))->assertDontSee('Risposta offensiva')->assertSee('Risposta gentile');

        $component->call('restoreReply', $bad->id);
        $this->assertNull($bad->refresh()->hidden_at);

        $component
            ->call('ask', 'delete_reply', $bad->id)
            ->assertSee('La risposta di Troll viene cancellata.')
            ->call('confirm');

        $this->assertModelMissing($bad);
        $this->assertModelExists($kept);
    }

    public function test_the_navigation_badge_counts_the_flagged_posts(): void
    {
        $this->communityPost('Utente anonimo', ['reports_count' => 4]);
        $this->communityPost('Davide Corti', ['reports_count' => 2, 'hidden_at' => now()]);

        $groups = app(AdminNavigation::class)->groups();
        $community = collect($groups)->pluck('items')->flatten(1)->firstWhere('route', 'admin.community');

        $this->assertSame(1, $community['count']);
    }
}
