<?php

namespace App\Livewire\Profile;

use App\Enums\ProductType;
use App\Models\OrderItem\OrderItem;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProfileEvents extends Component
{
    /** Tab attiva (deep-link ?tab=passati come gli ordini). */
    #[Url(as: 'tab', except: 'programma')]
    public string $tab = 'programma';

    /** chiave stato → chiave lang della label (le chiavi restano la whitelist ?tab). */
    public const TABS = ['programma' => 'profile.tab_upcoming', 'passati' => 'profile.tab_past'];

    public function mount(): void
    {
        $this->normalizeTab();
    }

    /** Il binding #[Url] (e il wire:model delle tab) accetta qualunque valore: fuori whitelist → 'programma'. */
    private function normalizeTab(): void
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'programma';
        }
    }

    public function render()
    {
        $this->normalizeTab();

        return view('livewire.profile.profile-events', [
            'tabs' => array_map(fn ($key) => __($key), self::TABS),
            'events' => $this->bookedEvents(),
        ])->title(__('profile.title_events'));
    }

    /**
     * Eventi che l'utente ha davvero acquistato, letti dagli snapshot di
     * order_items come "I miei ordini" (mai il prodotto vivo: la card resta
     * leggibile anche se l'evento sparisce dal catalogo). Bucket identico agli
     * ordini — passato ⇔ booked_until < adesso — e regali esclusi: chi compra
     * non partecipa, la stessa regola di OrderQueryService::profileRouteFor().
     *
     * @return list<array{id: int, title: string, tag: string, photo: ?string, time: ?string, location: ?string, price: string}>
     */
    private function bookedEvents(): array
    {
        $past = $this->tab === 'passati';

        return OrderItem::query()
            ->where('product_type', ProductType::Event)
            ->where('is_gift', false)
            ->whereHas('order', fn (Builder $order) => $order->where('user_id', Auth::id()))
            ->when(
                $past,
                fn (Builder $query) => $query->whereNotNull('booked_until')->where('booked_until', '<', now()),
                // Riga senza finestra (evento a data da definire): resta "in programma",
                // come l'ordine senza booked_until in OrderQueryService::isPast().
                fn (Builder $query) => $query->where(
                    fn (Builder $window) => $window->whereNull('booked_until')->orWhere('booked_until', '>=', now()),
                ),
            )
            // In programma: il più vicino per primo. Passati: il più recente per primo.
            ->orderBy('booked_from', $past ? 'desc' : 'asc')
            ->get()
            ->map(fn (OrderItem $item): array => $this->presentEvent($item))
            ->values()
            ->all();
    }

    /**
     * Card evento del blade dal solo snapshot riga: tag dal ProductType,
     * orario da booked_from con lo stesso formato della griglia eventi.
     *
     * @return array{id: int, title: string, tag: string, photo: ?string, time: ?string, location: ?string, price: string}
     */
    private function presentEvent(OrderItem $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'tag' => $item->product_type->label(),
            'photo' => $item->photo_url,
            'time' => $item->booked_from !== null ? Format::eventTime($item->booked_from) : null,
            'location' => $item->location,
            // Evento gratuito: "Gratis" come nelle card del catalogo, non "0,00 €".
            'price' => $item->price_cents === 0 ? __('format.free') : Format::money($item->price_cents),
        ];
    }
}
