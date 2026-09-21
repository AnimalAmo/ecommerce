<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

/**
 * I numeri "da fare" del pannello: badge della navigazione e riquadri della
 * home. Letti con il query builder e non dai model perché le schede sospese o
 * in attesa sono escluse dallo scope di visibilità del catalogo, e qui servono
 * proprio quelle.
 *
 * Singleton per request (registrato in AppServiceProvider): il layout e la
 * home chiedono gli stessi numeri, una query per voce basta.
 */
class AdminCounters
{
    /** @var array<string, int> */
    private array $memo = [];

    public const CATALOG_TABLES = ['structures', 'events', 'smartbox_packages'];

    /** Schede in attesa di approvazione, sulle tre famiglie. */
    public function pendingApprovals(): int
    {
        return $this->memo[__FUNCTION__] ??= collect(self::CATALOG_TABLES)
            ->sum(fn (string $table): int => DB::table($table)->where('approval_status', 'pending')->count());
    }

    /** Recensioni scritte dai clienti e non ancora moderate. */
    public function pendingReviews(): int
    {
        return $this->memo[__FUNCTION__] ??= DB::table('reviews')->where('status', 'pending')->count();
    }

    /** Post community segnalati, ancora visibili e non ancora valutati. */
    public function flaggedPosts(): int
    {
        return $this->memo[__FUNCTION__] ??= DB::table('community_posts')
            ->where('reports_count', '>', 0)
            ->whereNull('hidden_at')
            ->count();
    }

    /** Messaggi dal modulo contatti non ancora lavorati né archiviati. */
    public function openMessages(): int
    {
        return $this->memo[__FUNCTION__] ??= DB::table('contact_messages')
            ->whereNull('handled_at')
            ->whereNull('archived_at')
            ->count();
    }

    /** Candidature "Lavora con noi" non ancora lavorate né archiviate. */
    public function openApplications(): int
    {
        return $this->memo[__FUNCTION__] ??= DB::table('partner_applications')
            ->whereNull('handled_at')
            ->whereNull('archived_at')
            ->count();
    }

    public function openInbox(): int
    {
        return $this->openMessages() + $this->openApplications();
    }
}
