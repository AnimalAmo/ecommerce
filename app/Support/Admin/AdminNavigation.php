<?php

namespace App\Support\Admin;

use App\Services\Admin\AdminCounters;

/**
 * Voci della colonna di navigazione del pannello, nell'ordine del design.
 *
 * `active` sono i pattern di nome rotta che accendono la voce: la scheda di
 * una struttura tiene acceso "Schede pubblicate", l'editor di un articolo
 * tiene acceso "Animal Times".
 */
class AdminNavigation
{
    public function __construct(private readonly AdminCounters $counters) {}

    /**
     * @return list<array{label: string, items: list<array{label: string, route: string, active: list<string>, count: int|null}>}>
     */
    public function groups(): array
    {
        return [
            ['label' => 'Panoramica', 'items' => [
                $this->item('Home pannello', 'admin.home', ['admin.home', 'admin.search']),
            ]],
            ['label' => 'Catalogo', 'items' => [
                $this->item('Schede pubblicate', 'admin.catalog.index', ['admin.catalog.*']),
                $this->item('Da approvare', 'admin.approvals', ['admin.approvals'], $this->counters->pendingApprovals()),
                $this->item('Recensioni', 'admin.reviews', ['admin.reviews'], $this->counters->pendingReviews()),
            ]],
            ['label' => 'Contenuti', 'items' => [
                $this->item('Pagine', 'admin.pages.index', ['admin.pages.*']),
                $this->item('Animal Times', 'admin.articles.index', ['admin.articles.*']),
                $this->item('Domande frequenti', 'admin.faqs', ['admin.faqs']),
                $this->item('Community', 'admin.community', ['admin.community'], $this->counters->flaggedPosts()),
            ]],
            ['label' => 'Persone', 'items' => [
                $this->item('Iscritti', 'admin.users.index', ['admin.users.*']),
                $this->item('Contatti e candidature', 'admin.inbox', ['admin.inbox'], $this->counters->openInbox()),
                $this->item('Newsletter', 'admin.newsletter.index', ['admin.newsletter.*']),
            ]],
            ['label' => 'Denaro', 'items' => [
                $this->item('Incassi', 'admin.payouts', ['admin.payouts*']),
            ]],
        ];
    }

    /**
     * @param  list<string>  $active
     * @return array{label: string, route: string, active: list<string>, count: int|null}
     */
    private function item(string $label, string $route, array $active, ?int $count = null): array
    {
        return [
            'label' => $label,
            'route' => $route,
            'active' => $active,
            // Zero non si mostra: il badge dice "c'è qualcosa da fare".
            'count' => $count ?: null,
        ];
    }
}
