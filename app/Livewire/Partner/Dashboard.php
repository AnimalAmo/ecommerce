<?php

namespace App\Livewire\Partner;

use App\Enums\OrderStatus;
use App\Models\Event\Event;
use App\Models\Favorite\Favorite;
use App\Models\OrderItem\OrderItem;
use App\Models\Scopes\CatalogVisibleScope;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\Structure\StructureDraft;
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
            // Avviso lasciato da completeDraft: un toast prima del redirect si perdeva.
            'notice' => session('partner.notice'),
            'awaitingCount' => $this->awaitingCount(),
        ])->title(__('partner.dashboard.title'));
    }

    /**
     * Servizi chiusi dal partner quando non poteva ancora essere pagato (P4):
     * li pubblica il collegamento Stripe. Senza questo numero il partner finiva
     * il wizard e non trovava il servizio né a catalogo né qui. Zero per chi
     * può già pubblicare: le sue bozze in attesa sono bozze non pubblicabili,
     * e "Collega Stripe" gli chiederebbe qualcosa che ha già fatto.
     */
    private function awaitingCount(): int
    {
        if (Auth::user()->partnerProfile?->canPublish() === true) {
            return 0;
        }

        return StructureDraft::query()
            ->where('user_id', Auth::id())
            ->awaitingPublication()
            ->count();
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
            // Venduto = prenotazione valida: pagata online o confermata da pagare in struttura.
            ['label' => 'partner.dashboard.stat_sold', 'value' => $this->bookings(OrderStatus::bookingStatuses())],
            ['label' => 'partner.dashboard.stat_cancelled', 'value' => $this->bookings([OrderStatus::Cancelled])],
            ['label' => 'partner.dashboard.stat_saved', 'value' => $this->saved()],
        ];
    }

    /**
     * Righe ordine dei prodotti del partner con la testata in uno degli stati dati.
     *
     * @param  list<OrderStatus>  $statuses
     */
    private function bookings(array $statuses): int
    {
        return OrderItem::query()
            ->whereHas('order', fn (Builder $query) => $query->whereIn('status', $statuses))
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
        // Sospese o in attesa restano del partner: i numeri della sua dashboard le contano.
        $query->withoutGlobalScope(CatalogVisibleScope::class)
            ->whereNotNull('user_id')
            ->where('user_id', Auth::id());
    }
}
