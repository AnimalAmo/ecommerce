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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    /** Famiglie a catalogo che un partner può possedere (target dei morph). */
    private const OWNED_TYPES = [Structure::class, Event::class, SmartboxPackage::class];

    /**
     * Bozze in attesa per famiglia, memorizzate per il render. Privata, quindi
     * fuori dal payload Livewire: si ricalcola a ogni richiesta.
     *
     * @var array<string, int>|null
     */
    private ?array $awaitingCache = null;

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
            'smartboxAwaitingCount' => $this->smartboxAwaitingCount(),
        ])->title(__('partner.dashboard.title'));
    }

    /**
     * Servizi chiusi dal partner quando non poteva ancora essere pagato (P4):
     * li pubblica il collegamento Stripe. Senza questo numero il partner finiva
     * il wizard e non trovava il servizio né a catalogo né qui. Zero per chi
     * può già pubblicare: le sue bozze in attesa sono bozze non pubblicabili,
     * e "Collega Stripe" gli chiederebbe qualcosa che ha già fatto.
     *
     * Le smartbox restano fuori da questo conteggio: hanno un avviso loro, e
     * per un partner che incassa fuori dalla piattaforma "Collega Stripe" non
     * sarebbe nemmeno la cosa da fare (27/09/2026).
     */
    private function awaitingCount(): int
    {
        if (Auth::user()->partnerProfile?->canPublish() === true) {
            return 0;
        }

        return array_sum(Arr::except($this->awaitingByFamily(), 'smartbox'));
    }

    /**
     * Smartbox ferme perché il partner non può pubblicarne (richiesta della
     * cliente del 27/09/2026): sono le bozze in attesa più quelle già ritirate
     * dalla vetrina, che il ritiro rimette in attesa.
     *
     * Conteggio a sé e non dentro `awaitingCount()`: quello esce zero per chi
     * `canPublish()` ammette, e un partner che incassa in struttura lo è — con
     * una smartbox pronta oggi non vedrebbe niente, che è il difetto da
     * correggere.
     */
    private function smartboxAwaitingCount(): int
    {
        if (Auth::user()->partnerProfile?->canPublishFamily('smartbox') === true) {
            return 0;
        }

        return $this->awaitingByFamily()['smartbox'] ?? 0;
    }

    /**
     * Bozze in attesa del partner, contate per famiglia. Una query sola, letta
     * dai due avvisi: nessuna bozza va contata due volte.
     *
     * Si raggruppa in PHP e non in SQL perché `service_category` nulla vale
     * "struttura" (`StructureDraft::family()`): un `!= 'smartbox'` in SQL
     * scarterebbe in silenzio proprio quelle righe, dato che il confronto con
     * NULL non è mai vero.
     *
     * @return array<string, int>
     */
    private function awaitingByFamily(): array
    {
        return $this->awaitingCache ??= StructureDraft::query()
            ->where('user_id', Auth::id())
            ->awaitingPublication()
            ->get(['id', 'service_category'])
            ->countBy(fn (StructureDraft $draft): string => $draft->family())
            ->all();
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
