<?php

namespace App\Livewire\Partner\Bookings;

use App\Enums\ProductType;
use App\Models\OrderItem\OrderItem;
use App\Services\Pricing\BookingPricingService;
use App\Support\Format;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Dettaglio prenotazione (XD "Dettaglio prenotazione strutture/eventi/
 * attività/smartbox"): info cliente + info prenotazione su due colonne,
 * foto e bottone Stampa. Fonte: la riga ordine (snapshot B2C) di un
 * prodotto del partner; le sezioni variano per famiglia come nei mockup
 * (eventi/attività: data+orario+durata; smartbox: solo validità).
 */
class PartnerBookingDetail extends Component
{
    public OrderItem $booking;

    public function mount(OrderItem $booking): void
    {
        // Solo le prenotazioni dei PROPRI prodotti a catalogo.
        abort_unless($booking->purchasable?->user_id === Auth::id(), 403);

        $this->booking = $booking->load('order.payment');
    }

    public function render()
    {
        return view('livewire.partner.bookings.detail', [
            'title' => $this->booking->title,
            'photo' => $this->booking->photo_url ?: $this->booking->purchasable?->imageUrl(),
            'customer' => [
                'detail_first_name' => $this->booking->order->first_name,
                'detail_last_name' => $this->booking->order->last_name,
                'detail_email' => $this->booking->order->email,
                'detail_phone' => $this->booking->order->phone,
            ],
            'left' => array_filter($this->leftRows(), fn ($value) => filled($value)),
            'right' => array_filter($this->rightRows(), fn ($value) => filled($value)),
        ])->title(__('partner.bookings.detail_title'));
    }

    /** Famiglia-tab dal product_type snapshot della riga. */
    public function family(): string
    {
        return match ($this->booking->product_type) {
            ProductType::Event => 'eventi',
            ProductType::Activity => 'attivita',
            ProductType::Stay, ProductType::Wellness, ProductType::Adventure => 'smartbox',
            default => 'strutture',
        };
    }

    /** @return array<string, mixed> */
    private function leftRows(): array
    {
        $item = $this->booking;

        $rows = [
            'detail_id' => $item->order->order_number,
            'detail_payment_method' => $item->order->payment?->payment_method?->label(),
        ];

        if ($this->family() === 'smartbox') {
            return $rows + ['detail_people' => BookingPricingService::persons($item->options ?? [])];
        }

        $time = in_array($this->family(), ['eventi', 'attivita'], true)
            ? $item->booked_from?->format('H:i')
            : null;

        return $rows + [
            'detail_booking_date' => $this->bookedDates(),
            // 00:00 = finestra senza orario significativo (es. attività per data): riga omessa.
            'detail_time' => $time !== '00:00' ? $time : null,
        ];
    }

    /** @return array<string, mixed> */
    private function rightRows(): array
    {
        $item = $this->booking;

        if ($this->family() === 'smartbox') {
            return [
                'detail_validity' => $item->booked_from && $item->booked_until
                    ? Format::dateRange($item->booked_from, $item->booked_until)
                    : null,
                'detail_price' => Format::money($item->price_cents),
            ];
        }

        return [
            // Per eventi/attività il mockup mostra il luogo sotto "Struttura:".
            'detail_structure' => $this->family() === 'strutture' ? $item->title : $item->location,
            'detail_price' => Format::money($item->price_cents),
            'detail_people' => BookingPricingService::persons($item->options ?? []),
            'detail_duration' => $this->duration(),
        ];
    }

    private function bookedDates(): ?string
    {
        $item = $this->booking;

        if ($item->booked_from === null) {
            return null;
        }

        if ($this->family() === 'eventi' || $item->booked_until === null || $item->booked_from->isSameDay($item->booked_until)) {
            return Format::dateShort($item->booked_from);
        }

        return Format::dateRange($item->booked_from, $item->booked_until);
    }

    /** Strutture: notti; attività su più giorni: giorni; stesso giorno: ore. */
    private function duration(): ?string
    {
        $item = $this->booking;

        if ($item->booked_from === null || $item->booked_until === null) {
            return null;
        }

        $days = (int) $item->booked_from->copy()->startOfDay()->diffInDays($item->booked_until->copy()->startOfDay());

        if ($this->family() === 'strutture') {
            return $days > 0 ? trans_choice('partner.bookings.duration_nights', $days, ['count' => $days]) : null;
        }

        if ($days > 0) {
            return trans_choice('partner.bookings.duration_days', $days + 1, ['count' => $days + 1]);
        }

        $hours = (int) round($item->booked_from->diffInMinutes($item->booked_until) / 60);

        return $hours > 0 ? trans_choice('partner.bookings.duration_hours', $hours, ['count' => $hours]) : null;
    }
}
