<?php

namespace App\Services;

use App\Models\Community\CommunityPost;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Post della community (Animal Network): lettura per le viste e scrittura dal
 * composer / dalla barra di risposta.
 *
 * I componenti ricevono array già pronti per il markup (colore chip risolto, autore
 * firmato "(Io)" quando è il proprio): le viste non devono interrogare i modelli e
 * lista e dettaglio condividono la stessa forma.
 */
class CommunityService
{
    /** Tipologie/tag disponibili (chips composer, menu "Filtra tipologia", pannello filtri mobile). */
    public const TAGS = ['Avventura', 'Soggiorno', 'Benessere', 'Eventi', 'Attività', 'Strutture', 'Servizi'];

    /** Colore chip del tag sulla card (palette per tag da direttiva; "Domanda" grigio XD). */
    public const TAG_COLORS = [
        'Avventura' => '#3E72FF',
        'Soggiorno' => '#8DE0FF',
        'Benessere' => '#8DABFF',
        'Eventi' => '#C59FFD',
        'Attività' => '#8E53E6',
        'Strutture' => '#FF9F3E',
        'Servizi' => '#FFE13E',
        'Domanda' => '#555555',
    ];

    /** @return array<int, array<string, mixed>> */
    public function posts(?int $userId = null): array
    {
        return CommunityPost::with('replies')
            ->orderBy('id')
            ->get()
            ->map(fn (CommunityPost $post): array => $this->present($post, $userId))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id, ?int $userId = null): ?array
    {
        $post = CommunityPost::with('replies')->find($id);

        return $post === null ? null : $this->present($post, $userId);
    }

    /** Pubblica dal composer: il titolo è la prima riga del testo, troncata. */
    public function publish(User $user, string $body, array $tags): CommunityPost
    {
        $selected = array_values(array_intersect($tags, self::TAGS));

        return CommunityPost::create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'title' => Str::limit(trim(Str::before($body, "\n")), 60, '…'),
            'tag' => $selected[0] ?? self::TAGS[0],
            'body' => $body,
        ]);
    }

    public function reply(int $postId, User $user, string $body): void
    {
        CommunityPost::findOrFail($postId)->replies()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'body' => $body,
        ]);
    }

    public function tagColor(string $tag): string
    {
        return self::TAG_COLORS[$tag] ?? '#555555';
    }

    /** @return array<string, mixed> */
    private function present(CommunityPost $post, ?int $userId): array
    {
        $mine = $post->user_id !== null && $post->user_id === $userId;

        return [
            'id' => $post->id,
            'title' => $post->title,
            'tag' => $post->tag,
            'tagColor' => $this->tagColor($post->tag),
            'author' => $this->signature($post->author_name, $post->user_id, $userId),
            'body' => $post->body,
            'replies' => $post->replies
                ->map(fn ($reply): array => [
                    'author' => $this->signature($reply->author_name, $reply->user_id, $userId),
                    'body' => $reply->body,
                ])
                ->all(),
            'mine' => $mine,
        ];
    }

    /** XD firma i propri interventi con "Nome (Io)" (artboard "Dettaglio post - scrivi – 1"). */
    private function signature(string $name, ?int $authorId, ?int $userId): string
    {
        return $authorId !== null && $authorId === $userId
            ? __('community.author_me', ['name' => $name])
            : $name;
    }
}
