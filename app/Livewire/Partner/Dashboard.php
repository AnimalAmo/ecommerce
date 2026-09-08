<?php

namespace App\Livewire\Partner;

use App\Enums\OrderStatus;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\OrderItem\OrderItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    /** Famiglie a catalogo che un partner può possedere (target dei morph). */
    private const OWNED_TYPES = [Structure::class, Event::class, SmartboxPackage::class];

    /**
     * Il nome del saluto e le statistiche si leggono a ogni render da chi è
     * loggato, non da proprietà pubbliche: la rotta è dietro `auth`+`partner`,
     * quindi l'utente c'è sempre, e così i valori non viaggiano nel payload
     * Livewire (dove il client potrebbe riscriverli).
     */
    public function render()
    {
        return view('livewire.partner.dashboard', [
            'partnerName' => Auth::user()->first_name,
            'stats' => $this->stats(),
        ])->title(__('partner.dashboard.title'));
    }

    /**
     * Statistiche reali del partner loggato. Il mockup XD mostrava tre numeri
     * fissi (112/21/236) con la loro variazione %: a catalogo vuoto ogni
     * partner si vedeva attribuire vendite di nessuno. Qui si contano le sue
     * righe ordine e i suoi preferiti — a inizio attività sono zeri, ed è la
     * verità. La variazione % non c'è: non esiste uno storico da confrontare
     * e calcolarla su un periodo inventato sarebbe lo stesso difetto.
     *
     * @return list<array{label: string, value: int}>
     */
    private function stats(): array
    {
        return [
            ['label' => 'partner.dashboard.stat_sold', 'value' => $this->bookings(OrderStatus::Paid)],
            ['label' => 'partner.dashboard.stat_cancelled', 'value' => $this->bookings(OrderStatus::Cancelled)],
            ['label' => 'partner.dashboard.stat_saved', 'value' => $this->saved()],
        ];
    }

    /** Righe ordine dei prodotti del partner con la testata nello stato dato. */
    private function bookings(OrderStatus $status): int
    {
        return OrderItem::query()
            ->whereHas('order', fn (Builder $query) => $query->where('status', $status))
            ->whereHasMorph('purchasable', self::OWNED_TYPES, $this->ownedBy(...))
            ->count();
    }

    /** Quante volte i prodotti del partner sono stati salvati tra i preferiti. */
    private function saved(): int
    {
        return Favorite::query()
            ->whereHasMorph('favoritable', self::OWNED_TYPES, $this->ownedBy(...))
            ->count();
    }

    /**
     * Vincolo di proprietà. Il `whereNotNull` non è ridondante: le righe del
     * catalogo mock non hanno proprietario e `where('user_id', null)` in SQL
     * diventerebbe `IS NULL`, attribuendole al partner loggato.
     */
    private function ownedBy(Builder $query): void
    {
        $query->whereNotNull('user_id')->where('user_id', Auth::id());
    }
}
