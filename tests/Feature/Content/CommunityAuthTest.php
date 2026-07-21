<?php

namespace Tests\Feature\Content;

use App\Livewire\Content\Community;
use App\Models\User;
use Database\Seeders\CommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CommunitySeeder::class);
    }

    public function test_guest_cannot_publish_and_is_prompted_to_log_in(): void
    {
        Livewire::test(Community::class)
            ->set('composerBody', 'Un post da ospite')
            ->call('publish')
            ->assertDispatched('modal-show', name: 'login');

        // Nessun post pubblicato: la lista resta ai soli campioni seed.
        Livewire::test(Community::class)->assertDontSee('Un post da ospite');
    }

    public function test_guest_cannot_reply_and_is_prompted_to_log_in(): void
    {
        Livewire::test(Community::class)
            ->set('replyDrafts.1', 'Una risposta da ospite')
            ->call('reply', 1)
            ->assertDispatched('modal-show', name: 'login')
            ->assertDontSee('Una risposta da ospite');
    }

    public function test_guest_sees_the_login_cta_not_the_composer(): void
    {
        Livewire::test(Community::class)
            ->assertSee(__('community.login_to_post'))
            ->assertDontSee(__('community.composer_heading'));
    }

    public function test_logged_in_user_can_publish_with_their_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Giulia', 'last_name' => 'Rossi']);

        Livewire::actingAs($user)->test(Community::class)
            ->assertSee(__('community.composer_heading'))
            ->set('composerBody', 'Il mio primo post')
            ->set('composerTags', ['Avventura'])
            ->call('publish')
            ->assertNotDispatched('modal-show')
            ->assertSee('Il mio primo post')
            ->assertSee($user->name);
    }

    public function test_logged_in_user_can_reply(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Community::class)
            ->set('replyDrafts.1', 'La mia risposta')
            ->call('reply', 1)
            ->assertNotDispatched('modal-show')
            ->assertSee('La mia risposta');

        $this->assertDatabaseHas('community_post_replies', [
            'community_post_id' => 1,
            'user_id' => $user->id,
            'body' => 'La mia risposta',
        ]);
    }

    public function test_publishing_persists_the_post_and_raises_the_mobile_alert(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Community::class)
            ->set('composerBody', 'Domanda nuova')
            ->set('composerTags', ['Benessere'])
            ->call('publish')
            // Sweet alert XD "Community – sweet alert": overlay, non un <dialog>.
            ->assertSet('shared', true)
            ->assertSee(__('community.shared_title'))
            ->call('dismissShared')
            ->assertSet('shared', false);

        $this->assertDatabaseHas('community_posts', [
            'user_id' => $user->id,
            'title' => 'Domanda nuova',
            'tag' => 'Benessere',
        ]);
    }

    public function test_the_alert_does_not_appear_when_a_guest_is_sent_to_the_login(): void
    {
        Livewire::test(Community::class)
            ->set('composerBody', 'Un post da ospite')
            ->call('publish')
            ->assertSet('shared', false);
    }
}
