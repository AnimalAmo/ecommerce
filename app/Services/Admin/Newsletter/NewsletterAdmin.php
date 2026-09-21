<?php

namespace App\Services\Admin\Newsletter;

use App\Models\Newsletter\NewsletterCampaign;
use App\Models\Newsletter\NewsletterSubscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * I numeri e gli elenchi della schermata Newsletter del pannello. I filtri
 * degli iscritti sono gli stessi per la tabella e per l'esportazione, così il
 * file e lo schermo non possono dire cose diverse.
 */
class NewsletterAdmin
{
    /** Filtro "Soppresso": le due liste di soppressione insieme. */
    public const FILTER_SUPPRESSED = 'suppressed';

    public const PER_PAGE = 10;

    /**
     * @param  array{search?: string, status?: string, source?: string, locale?: string}  $filters
     * @return Builder<NewsletterSubscriber>
     */
    public function subscribers(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');
        $source = (string) ($filters['source'] ?? '');
        $locale = (string) ($filters['locale'] ?? '');

        return NewsletterSubscriber::query()
            ->when($search !== '', fn (Builder $query) => $query->where('email', 'like', '%'.mb_strtolower($search).'%'))
            ->when($status === self::FILTER_SUPPRESSED, fn (Builder $query) => $query->whereIn('status', NewsletterSubscriber::SUPPRESSED))
            ->when($status !== '' && $status !== self::FILTER_SUPPRESSED, fn (Builder $query) => $query->where('status', $status))
            ->when($source !== '', fn (Builder $query) => $query->where('source', $source))
            ->when(in_array($locale, ['it', 'en'], true), fn (Builder $query) => $query->where('locale', $locale))
            ->orderByDesc('id');
    }

    /** @param  array{search?: string, status?: string, source?: string, locale?: string}  $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->subscribers($filters)->paginate(self::PER_PAGE);
    }

    /** @return Collection<int, NewsletterCampaign> */
    public function campaigns(int $limit = 20): Collection
    {
        return NewsletterCampaign::query()->latest('id')->limit($limit)->get();
    }

    /** L'ultima campagna partita: la base di "Aperture ultimo invio". */
    public function lastSent(): ?NewsletterCampaign
    {
        return NewsletterCampaign::query()
            ->whereIn('status', [NewsletterCampaign::STATUS_SENT, NewsletterCampaign::STATUS_SENDING])
            ->latest('started_at')
            ->first();
    }

    /**
     * @return array{confirmed: int, confirmedThisMonth: int, pending: int, pendingLegacy: int, unsubscribed90: int}
     */
    public function totals(): array
    {
        return [
            'confirmed' => NewsletterSubscriber::confirmed()->count(),
            'confirmedThisMonth' => NewsletterSubscriber::confirmed()->where('confirmed_at', '>=', now()->startOfMonth())->count(),
            'pending' => NewsletterSubscriber::where('status', NewsletterSubscriber::STATUS_PENDING)->count(),
            'pendingLegacy' => NewsletterSubscriber::where('status', NewsletterSubscriber::STATUS_PENDING)->where('legacy', true)->count(),
            'unsubscribed90' => NewsletterSubscriber::where('status', NewsletterSubscriber::STATUS_UNSUBSCRIBED)
                ->where('unsubscribed_at', '>=', now()->subDays(90))
                ->count(),
        ];
    }

    /** Etichetta della riga "Consenso": stato e momento che lo prova. */
    public function proofLine(NewsletterSubscriber $subscriber): string
    {
        $at = fn ($date): string => $date?->translatedFormat('j M Y, H:i') ?? '—';

        return match ($subscriber->status) {
            NewsletterSubscriber::STATUS_CONFIRMED => __('admin-newsletter.proof_line.confirmed', ['date' => $at($subscriber->confirmed_at)]),
            NewsletterSubscriber::STATUS_UNSUBSCRIBED => __('admin-newsletter.proof_line.unsubscribed', ['date' => $at($subscriber->unsubscribed_at)]),
            NewsletterSubscriber::STATUS_BOUNCED => __('admin-newsletter.proof_line.bounced', ['date' => $at($subscriber->suppressed_at)]),
            NewsletterSubscriber::STATUS_COMPLAINED => __('admin-newsletter.proof_line.complained', ['date' => $at($subscriber->suppressed_at)]),
            default => $subscriber->confirmation_sent_at !== null
                ? __('admin-newsletter.proof_line.pending_sent', ['date' => $subscriber->confirmation_sent_at->translatedFormat('j M Y')])
                : __('admin-newsletter.proof_line.no_proof'),
        };
    }

    /** Colore del badge di stato di un iscritto (x-admin.badge). */
    public static function statusTone(string $status): string
    {
        return match ($status) {
            NewsletterSubscriber::STATUS_CONFIRMED => 'success',
            NewsletterSubscriber::STATUS_PENDING => 'warning',
            NewsletterSubscriber::STATUS_UNSUBSCRIBED => 'muted',
            default => 'danger',
        };
    }

    /** Colore del badge di stato di una campagna. */
    public static function campaignTone(string $status): string
    {
        return match ($status) {
            NewsletterCampaign::STATUS_SENT => 'success',
            NewsletterCampaign::STATUS_SENDING => 'info',
            NewsletterCampaign::STATUS_FAILED => 'danger',
            default => 'muted',
        };
    }
}
