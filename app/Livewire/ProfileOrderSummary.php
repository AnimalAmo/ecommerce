<?php

namespace App\Livewire;

use App\Models\Order\Order;
use App\Services\Orders\OrderQueryService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfileOrderSummary extends Component
{
    /** order_number dalla rotta ({order}), scopato sull'utente autenticato (altrui → 404). */
    public string $order = '';

    /** Ordine passato → variante XD "– 1": box 206px con "Scrivi una recensione". */
    public bool $past = false;

    /** Riga ordine in recensione nel pop-up (XD "Pop-up scrivi recensione"); null = chiuso. */
    public ?int $reviewItemId = null;

    public string $reviewTitle = '';

    public string $reviewText = '';

    /** Ordine risolto una volta per request (mount, azioni e render). */
    private ?Order $resolvedOrder = null;

    public function mount(OrderQueryService $orders): void
    {
        // Bucket derivato: stesso criterio della lista (max booked_until < now()).
        $this->past = $orders->isPast($this->orderModel());
    }

    /** "Scrivi una recensione": apre il pop-up con i campi azzerati. */
    public function openReview(int $itemId): void
    {
        if ($this->orderModel()->items->contains('id', $itemId)) {
            $this->reviewItemId = $itemId;
            $this->reviewTitle = '';
            $this->reviewText = '';

            Flux::modal('scrivi-recensione')->show();
        }
    }

    /** "Annulla": chiude il pop-up scartando i campi. */
    public function closeReview(): void
    {
        $this->reviewItemId = null;

        Flux::modal('scrivi-recensione')->close();
    }

    /** "Conferma": chiude e basta — submit ancora mock (recensioni reali = step 5). */
    public function confirmReview(): void
    {
        // TODO: invio recensione backend (step 5)
        $this->closeReview();
    }

    public function render(OrderQueryService $orders)
    {
        $items = $orders->presentItems($this->orderModel());

        return view('livewire.profile-order-summary', [
            'items' => $items,
            'reviewItem' => collect($items)->firstWhere('id', $this->reviewItemId),
        ])->title('Riepilogo ordine — AnimalAmo');
    }

    /** Ordine per order_number scopato sull'utente: inesistente o di altri → 404. */
    private function orderModel(): Order
    {
        if ($this->resolvedOrder === null) {
            $order = app(OrderQueryService::class)->findForUser(Auth::user(), $this->order);

            abort_if($order === null, 404);

            $this->resolvedOrder = $order;
        }

        return $this->resolvedOrder;
    }
}
