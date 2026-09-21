<?php

namespace App\Support\Admin;

use App\Services\Admin\AdminCounters;

/**
 * Voci della colonna di navigazione del pannello, nell'ordine del design.
 *
 * `active` sono i pattern di nome rotta che accendono la voce: la scheda di
 * una struttura tiene acceso "Schede pubblicate", l'editor di un articolo
 * tiene acceso "Animal Times". Il primo gruppo non ha etichetta: è la sola
 * Dashboard, con la sua icona.
 */
class AdminNavigation
{
    public function __construct(private readonly AdminCounters $counters) {}

    /**
     * @return list<array{label: string|null, items: list<array{label: string, route: string, active: list<string>, count: int|null, icon: string|null}>}>
     */
    public function groups(): array
    {
        return [
            ['label' => null, 'items' => [
                $this->item(__('admin.nav.home'), 'admin.home', ['admin.home', 'admin.search'], icon: 'dashboard'),
            ]],
            ['label' => __('admin.nav.groups.catalog'), 'items' => [
                $this->item(__('admin.nav.catalog'), 'admin.catalog.index', ['admin.catalog.*']),
                $this->item(__('admin.nav.approvals'), 'admin.approvals', ['admin.approvals'], $this->counters->pendingApprovals()),
                $this->item(__('admin.nav.reviews'), 'admin.reviews', ['admin.reviews'], $this->counters->pendingReviews()),
            ]],
            ['label' => __('admin.nav.groups.content'), 'items' => [
                $this->item(__('admin.nav.pages'), 'admin.pages.index', ['admin.pages.*']),
                $this->item(__('admin.nav.articles'), 'admin.articles.index', ['admin.articles.*']),
                $this->item(__('admin.nav.faqs'), 'admin.faqs', ['admin.faqs']),
                $this->item(__('admin.nav.community'), 'admin.community', ['admin.community'], $this->counters->flaggedPosts()),
            ]],
            ['label' => __('admin.nav.groups.people'), 'items' => [
                $this->item(__('admin.nav.users'), 'admin.users.index', ['admin.users.*']),
                $this->item(__('admin.nav.inbox'), 'admin.inbox', ['admin.inbox'], $this->counters->openInbox()),
                $this->item(__('admin.nav.newsletter'), 'admin.newsletter.index', ['admin.newsletter.*']),
            ]],
            ['label' => __('admin.nav.groups.money'), 'items' => [
                $this->item(__('admin.nav.payouts'), 'admin.payouts', ['admin.payouts*']),
            ]],
        ];
    }

    /**
     * @param  list<string>  $active
     * @return array{label: string, route: string, active: list<string>, count: int|null, icon: string|null}
     */
    private function item(string $label, string $route, array $active, ?int $count = null, ?string $icon = null): array
    {
        return [
            'label' => $label,
            'route' => $route,
            'active' => $active,
            // Zero non si mostra: il badge dice "c'è qualcosa da fare".
            'count' => $count ?: null,
            'icon' => $icon,
        ];
    }
}
