<?php

namespace App\Services\Orders;

use App\Models\Order\Order;
use App\Models\OrderItem\OrderItem;
use App\Models\User;
use App\Support\Format;
use Carbon\CarbonImmutable;

/**
 * Query + presentazione del profilo ordini (blueprint step 4): bucket
 * programma/passati derivato dalle finestre prenotate e card costruite SOLO
 * dagli snapshot di order_items (title/photo/product_type/options/booked_*):
 * mai il purchasable vivo, così le righe restano leggibili anche se il
 * prodotto viene rimosso dal catalogo. I componenti Livewire restano adapter.
 */
class OrderQueryService
{
    /**
     * Righe della lista ordini dell'utente (desc) per il tab richiesto,
     * già presentate per il blade (data ordine, conteggio articoli, strip
     * foto dagli snapshot, totale via Format::money).
     *
     * @return list<array{number: string, date: string, itemsLabel: string, photos: list<string>, price: string}>
     */
    public function listFor(User $user, bool $past): array
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Order $order): bool => $this->isPast($order) === $past)
            ->map(fn (Order $order): array => $this->presentRow($order))
            ->values()
            ->all();
    }

    /** Ordine del riepilogo scopato sull'utente: inesistente o altrui → null (404 nel componente). */
    public function findForUser(User $user, string $orderNumber): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('order_number', $orderNumber)
            ->with('items')
            ->first();
    }

    /**
     * Bucket derivato (blueprint): passato ⇔ max(items.booked_until) < now().
     * Righe tutte senza finestra (booked_until null) → l'ordine resta "in programma".
     */
    public function isPast(Order $order): bool
    {
        $until = $order->items->max('booked_until');

        return $until !== null && $until->isPast();
    }

    /**
     * Testata del riepilogo (conteggio | data | totale): stessa riga della lista,
     * usata dall'artboard app "Profilo – i miei ordini - riepilogo ordine".
     *
     * @return array{number: string, date: string, itemsLabel: string, photos: list<string>, price: string}
     */
    public function presentHeader(Order $order): array
    {
        return $this->presentRow($order);
    }

    /**
     * Card articolo del riepilogo, dal solo snapshot riga.
     *
     * @return list<array<string, mixed>>
     */
    public function presentItems(Order $order): array
    {
        return $order->items
            ->map(fn (OrderItem $item): array => $this->presentItem($item))
            ->values()
            ->all();
    }

    /** @return array{number: string, date: string, itemsLabel: string, photos: list<string>, price: string} */
    private function presentRow(Order $order): array
    {
        return [
            'number' => $order->order_number,
            'date' => Format::dateShort($order->created_at),
            'itemsLabel' => trans_choice('orders.items_count', $order->items->count(), ['count' => $order->items->count()]),
            'photos' => $order->items->pluck('photo_url')->filter()->values()->all(),
            'price' => Format::money($order->total_cents),
        ];
    }

    /**
     * Contratto card del blade riepilogo (stesse righe meta del checkout):
     * chip dal ProductType snapshot, date da booked_from/booked_until + options,
     * ospiti/animali via Format, prezzo via Format::money, metadati regalo.
     *
     * @return array<string, mixed>
     */
    private function presentItem(OrderItem $item): array
    {
        $options = $item->options ?? [];

        return [
            'id' => $item->id,
            'title' => $item->title,
            'tag' => $item->product_type->label(),
            'tagColor' => $item->product_type->color(),
            'photo' => $item->photo_url,
            'location' => $item->location,
            'dates' => $this->datesLabel($item, $options),
            'guests' => $this->guestsLabel($options),
            'animals' => isset($options['animals']) ? Format::animals($options['animals']) : null,
            'price' => Format::money($item->price_cents),
            'giftDedication' => $options['gift']['dedication'] ?? null,
            'giftMessage' => $options['gift']['message'] ?? null,
        ];
    }

    /**
     * Riga calendario della card: service = giorno + fascia oraria (come nel
     * carrello), smartbox = nessuna riga (la finestra è solo la validità),
     * altrimenti data singola o range dd/mm/YYYY dalla finestra prenotata.
     */
    private function datesLabel(OrderItem $item, array $options): ?string
    {
        if (isset($options['day'], $options['time_from'], $options['time_to'])) {
            return Format::dateShort(CarbonImmutable::parse($options['day'])).', '.$options['time_from'].' - '.$options['time_to'];
        }

        if ($item->purchasable_type === 'smartbox_package' || $item->booked_from === null) {
            return null;
        }

        if ($item->booked_until === null || $item->booked_until->isSameDay($item->booked_from)) {
            return Format::dateShort($item->booked_from);
        }

        return Format::dateRange($item->booked_from, $item->booked_until);
    }

    /** Etichetta ospiti dalle options snapshot (participants degli eventi → adulti, come al checkout). */
    private function guestsLabel(array $options): ?string
    {
        return match (true) {
            isset($options['guests']) => Format::guests($options['guests']),
            isset($options['participants']) => Format::guests(['adulti' => (int) $options['participants']]),
            default => null,
        };
    }
}
