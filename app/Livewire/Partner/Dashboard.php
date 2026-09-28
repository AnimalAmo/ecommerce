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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    /** Famiglie a catalogo che un partner può possedere (target dei morph). */
    private const OWNED_TYPES = [Structure::class, Event::class, SmartboxPackage::class];

    /**
     * Bozze in attesa, memorizzate per il render. Privata, quindi fuori dal
     * payload Livewire: si ricalcola a ogni richiesta.
     *
     * @var EloquentCollection<int, StructureDraft>|null
     */
    private ?EloquentCollection $awaitingCache = null;

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
     * Le smartbox di chi incassa fuori dalla piattaforma restano fuori da
     * questo conteggio: hanno un avviso loro, e "Collega Stripe" non sarebbe
     * nemmeno la cosa da fare (27/09/2026). Quelle di chi è online aspettano
     * Stripe come il resto, e si contano qui. Difetto F3 (28/09/2026): prima
     * prendevano l'avviso smartbox, e il partner leggeva due banner per lo
     * stesso fatto; la prima correzione le aveva tolte da entrambi, e chi in
     * attesa aveva solo smartbox non vedeva più nessun banner.
     */
    private function awaitingCount(): int
    {
        $profile = Auth::user()->partnerProfile;

        if ($profile?->canPublish() === true) {
            return 0;
        }

        $byFamily = $this->awaitingByFamily();

        if ($profile?->needsOnlinePaymentFor('smartbox') === true) {
            unset($byFamily['smartbox']);
        }

        return array_sum($byFamily);
    }

    /**
     * Smartbox ferme perché il partner incassa in struttura (richiesta della
     * cliente del 27/09/2026): sono le bozze in attesa più quelle già ritirate
     * dalla vetrina, che il ritiro rimette in attesa.
     *
     * Conteggio a sé e non dentro `awaitingCount()`: quello esce zero per chi
     * `canPublish()` ammette, e un partner che incassa in struttura lo è — con
     * una smartbox pronta oggi non vedrebbe niente, che è il difetto da
     * correggere.
     *
     * La condizione è la causa, non il gate. Difetto F3 (28/09/2026): con
     * `! canPublishFamily('smartbox')` contava anche le smartbox di chi è
     * online e deve solo finire Stripe, e quel partner vedeva insieme questo
     * avviso e quello di Stripe per lo stesso fatto. DraftPublisher, il
     * pannello admin e ora il badge di "I miei servizi" separano le due cause
     * allo stesso modo.
     */
    private function smartboxAwaitingCount(): int
    {
        if (Auth::user()->partnerProfile?->needsOnlinePaymentFor('smartbox') !== true) {
            return 0;
        }

        $awaiting = $this->awaitingDrafts()->filter(fn (StructureDraft $draft): bool => $draft->family() === 'smartbox');

        // Più le smartbox ritirate dalla vetrina che non hanno una bozza in
        // attesa: dal 28/09/2026 il ritiro non segna più la bozza (review
        // della fase 2), quindi senza questa riga il partner passato al
        // pagamento diretto vedrebbe sparire il cofanetto senza una parola.
        $withheld = SmartboxPackage::withHidden()
            ->where('user_id', Auth::id())
            ->whereNotNull('withheld_at')
            ->where(fn ($query) => $query
                ->whereNull('structure_draft_id')
                ->orWhereNotIn('structure_draft_id', $awaiting->modelKeys()))
            ->count();

        return $awaiting->count() + $withheld;
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
        return $this->awaitingDrafts()
            ->countBy(fn (StructureDraft $draft): string => $draft->family())
            ->all();
    }

    /** Le bozze in attesa del partner, lette una volta per richiesta. */
    private function awaitingDrafts(): EloquentCollection
    {
        return $this->awaitingCache ??= StructureDraft::query()
            ->where('user_id', Auth::id())
            ->awaitingPublication()
            ->get(['id', 'service_category']);
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
