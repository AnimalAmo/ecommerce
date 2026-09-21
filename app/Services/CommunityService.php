<?php

namespace App\Services;

use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Post della community (Animal Network): lettura per le viste e scrittura dal
 * composer / dalla barra di risposta.
 *
 * I componenti ricevono array già pronti per il markup (colore chip risolto, autore
 * firmato "(Io)" quando è il proprio): le viste non devono interrogare i modelli e
 * lista e dettaglio condividono la stessa forma.
 *
 * Solo post e risposte `visible()`: quello che il pannello nasconde sparisce dal
 * sito, lista, dettaglio e risposte comprese.
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
        $reported = $this->reportedBy($userId);

        return CommunityPost::query()
            ->visible()
            ->with(['replies' => fn ($query) => $query->visible()])
            ->orderBy('id')
            ->get()
            ->map(fn (CommunityPost $post): array => $this->present($post, $userId, $reported))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id, ?int $userId = null): ?array
    {
        $post = CommunityPost::query()
            ->visible()
            ->with(['replies' => fn ($query) => $query->visible()])
            ->find($id);

        return $post === null ? null : $this->present($post, $userId, $this->reportedBy($userId));
    }

    /**
     * "Segnala" dal sito: manda il post nella coda di moderazione del pannello.
     * Una segnalazione per utente e post; il proprio post non si segnala.
     *
     * @return 'reported'|'already'|'own'
     */
    public function report(User $user, int $postId): string
    {
        $post = CommunityPost::query()->visible()->findOrFail($postId);

        if ($post->user_id === $user->id) {
            return 'own';
        }

        return DB::transaction(function () use ($post, $user): string {
            $report = CommunityPostReport::firstOrCreate([
                'community_post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            if (! $report->wasRecentlyCreated) {
                return 'already';
            }

            // Query builder: la segnalazione non è una modifica del post (updated_at resta).
            CommunityPost::query()->whereKey($post->id)->toBase()->increment('reports_count');

            return 'reported';
        });
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
        CommunityPost::query()->visible()->findOrFail($postId)->replies()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'body' => $body,
        ]);
    }

    public function tagColor(string $tag): string
    {
        return self::TAG_COLORS[$tag] ?? '#555555';
    }

    /** @return list<int> id dei post già segnalati dall'utente */
    private function reportedBy(?int $userId): array
    {
        return $userId === null
            ? []
            : CommunityPostReport::query()->where('user_id', $userId)->pluck('community_post_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  list<int>  $reported
     * @return array<string, mixed>
     */
    private function present(CommunityPost $post, ?int $userId, array $reported = []): array
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
            'reported' => in_array($post->id, $reported, true),
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
