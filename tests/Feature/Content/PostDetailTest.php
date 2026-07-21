<?php

namespace Tests\Feature\Content;

use App\Livewire\Content\PostDetail;
use App\Models\Community\CommunityPost;
use App\Models\User;
use Database\Seeders\CommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CommunitySeeder::class);
    }

    public function test_the_post_and_its_replies_are_shown(): void
    {
        $post = CommunityPost::with('replies')->find(1);

        Livewire::test(PostDetail::class, ['post' => 1])
            ->assertSee($post->title)
            ->assertSee($post->author_name)
            ->assertSee($post->replies->first()->author_name);
    }

    public function test_an_unknown_post_is_a_404(): void
    {
        $this->get(route('community.post', 9999))->assertNotFound();
    }

    public function test_guest_cannot_reply_and_is_prompted_to_log_in(): void
    {
        Livewire::test(PostDetail::class, ['post' => 1])
            ->set('draft', 'Una risposta da ospite')
            ->call('reply')
            ->assertDispatched('modal-show', name: 'login')
            ->assertDontSee('Una risposta da ospite');

        $this->assertDatabaseMissing('community_post_replies', ['body' => 'Una risposta da ospite']);
    }

    public function test_logged_in_user_reply_is_persisted_and_signed_as_mine(): void
    {
        $user = User::factory()->create(['first_name' => 'Matteo', 'last_name' => 'Bianchi']);

        Livewire::actingAs($user)->test(PostDetail::class, ['post' => 1])
            ->set('draft', 'La mia risposta')
            ->call('reply')
            ->assertSet('draft', '')
            ->assertSee('La mia risposta')
            // XD "Dettaglio post - scrivi – 1": i propri interventi sono firmati "Nome (Io)".
            ->assertSee(__('community.author_me', ['name' => $user->name]));

        $this->assertDatabaseHas('community_post_replies', [
            'community_post_id' => 1,
            'user_id' => $user->id,
            'body' => 'La mia risposta',
        ]);
    }

    public function test_an_empty_reply_is_ignored(): void
    {
        $user = User::factory()->create();
        $before = CommunityPost::find(1)->replies()->count();

        Livewire::actingAs($user)->test(PostDetail::class, ['post' => 1])
            ->set('draft', '   ')
            ->call('reply');

        $this->assertSame($before, CommunityPost::find(1)->replies()->count());
    }
}
