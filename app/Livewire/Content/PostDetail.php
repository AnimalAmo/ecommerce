<?php

namespace App\Livewire\Content;

use App\Services\CommunityService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Dettaglio di un post della community (XD app "Dettaglio post - scrivi" e la sua
 * variante "– 1", che è la stessa schermata dopo l'invio della propria risposta).
 */
class PostDetail extends Component
{
    /** Id dal parametro {post}; il nome differisce per non collidere col binding Livewire. */
    public int $postId = 0;

    /** Bozza della barra "Scrivi qualcosa…". */
    public string $draft = '';

    public function mount(int $post, CommunityService $community): void
    {
        abort_if($community->find($post) === null, 404);

        $this->postId = $post;
    }

    /** Invia la risposta e la appende in coda. Solo utenti loggati (ospite → login). */
    public function reply(CommunityService $community): void
    {
        if (! Auth::check()) {
            Flux::modal('login')->show();

            return;
        }

        $body = trim($this->draft);

        if ($body === '') {
            return;
        }

        $community->reply($this->postId, Auth::user(), $body);

        $this->draft = '';
    }

    public function render(CommunityService $community)
    {
        $post = $community->find($this->postId, Auth::id());

        abort_if($post === null, 404);

        return view('livewire.content.post-detail', ['post' => $post])
            ->title($post['title'].' — '.__('community.title_mobile'));
    }
}
