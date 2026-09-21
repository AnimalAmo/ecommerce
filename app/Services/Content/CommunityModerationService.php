<?php

namespace App\Services\Content;

use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostReply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Moderazione della community dal pannello (design: is_community).
 *
 * Tre code: "Segnalati" (pubblicati con segnalazioni non ancora valutate),
 * "Tutti i post" (pubblicati) e "Rimossi" (nascosti). Ogni decisione del
 * pannello azzera le segnalazioni in attesa: un post lasciato pubblicato o
 * ripristinato torna in coda solo se arrivano segnalazioni nuove (di altri
 * utenti: una persona segnala un post una volta sola).
 */
class CommunityModerationService
{
    public const FLAGGED = 'flagged';

    public const ALL = 'all';

    public const HIDDEN = 'hidden';

    public const TABS = [self::FLAGGED, self::ALL, self::HIDDEN];

    public const PER_PAGE = 12;

    /** @return array<string, int> coda => quanti post */
    public function counts(): array
    {
        return [
            self::FLAGGED => CommunityPost::query()->flagged()->count(),
            self::ALL => CommunityPost::query()->visible()->count(),
            self::HIDDEN => CommunityPost::query()->whereNotNull('hidden_at')->count(),
        ];
    }

    /** @return LengthAwarePaginator<int, CommunityPost> */
    public function posts(string $tab, int $page = 1): LengthAwarePaginator
    {
        $query = CommunityPost::query()->withCount([
            'replies',
            'replies as hidden_replies_count' => fn ($query) => $query->whereNotNull('hidden_at'),
        ]);

        match ($tab) {
            self::FLAGGED => $query->flagged()->orderByDesc('reports_count')->latest('created_at'),
            self::HIDDEN => $query->whereNotNull('hidden_at')->latest('hidden_at'),
            default => $query->visible()->latest('created_at'),
        };

        return $query->orderByDesc('id')->paginate(self::PER_PAGE, page: $page);
    }

    /** @return Collection<int, CommunityPostReply> tutte, nascoste comprese */
    public function replies(CommunityPost $post): Collection
    {
        return $post->replies()->get();
    }

    /** "Lascia pubblicato": le segnalazioni in attesa sono state valutate. */
    public function keep(CommunityPost $post): void
    {
        $post->forceFill(['reports_count' => 0, 'moderated_at' => now()])->save();
    }

    /** "Rimuovi": il post sparisce dal sito con le sue risposte, ma resta ripristinabile. */
    public function hide(CommunityPost $post): void
    {
        $post->forceFill(['hidden_at' => now(), 'reports_count' => 0, 'moderated_at' => now()])->save();
    }

    public function restore(CommunityPost $post): void
    {
        $post->forceFill(['hidden_at' => null, 'reports_count' => 0, 'moderated_at' => now()])->save();
    }

    /** Cancellazione vera: risposte e segnalazioni scendono in cascata. */
    public function delete(CommunityPost $post): void
    {
        $post->delete();
    }

    public function hideReply(CommunityPostReply $reply): void
    {
        $reply->forceFill(['hidden_at' => now()])->save();
    }

    public function restoreReply(CommunityPostReply $reply): void
    {
        $reply->forceFill(['hidden_at' => null])->save();
    }

    public function deleteReply(CommunityPostReply $reply): void
    {
        $reply->delete();
    }
}
