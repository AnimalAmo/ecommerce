<?php

namespace App\Livewire\Partner\Bookings;

use App\Enums\ProductType;
use App\Models\Event\Event;
use App\Models\OrderItem\OrderItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Services\Pricing\BookingPricingService;
use App\Support\Format;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Prenotazioni partner (XD "Prenotazioni strutture/eventi/attività/smartbox"):
 * un'unica pagina con 4 flux:tab.panel per famiglia, ricerca live e filtro
 * data condivisi. Fonte: le righe ordine (OrderItem, snapshot B2C) i cui
 * prodotti a catalogo appartengono al partner (user_id scritto dalla
 * pipeline di pubblicazione).
 */
class PartnerBookings extends Component
{
    public const TABS = ['strutture', 'eventi', 'attivita', 'smartbox'];

    /** product_type (snapshot riga ordine) di ogni famiglia-tab. */
    private const TAB_TYPES = [
        'strutture' => [ProductType::Structure, ProductType::Service],
        'eventi' => [ProductType::Event],
        'attivita' => [ProductType::Activity],
        'smartbox' => [ProductType::Stay, ProductType::Wellness, ProductType::Adventure],
    ];

    public string $tab = 'strutture';

    public string $search = '';

    /** Data selezionata dal filtro (Y-m-d dal date-picker), null = nessun filtro. */
    public ?string $date = null;

    /** Guard sul binding di flux:tabs: valori fuori lista tornano alla prima tab. */
    public function updatedTab(string $value): void
    {
        if (! in_array($value, self::TABS, true)) {
            $this->tab = self::TABS[0];
        }
    }

    public function render()
    {
        $panels = [];
        foreach (self::TABS as $family) {
            $panels[$family] = [
                'columns' => $this->columnsFor($family),
                'rows' => $this->rowsFor($family),
            ];
        }

        return view('livewire.partner.bookings.index', ['panels' => $panels])
            ->title(__('partner.bookings.title'));
    }

    /**
     * Colonne della famiglia: chiave riga => chiave lang. Le famiglie
     * differiscono come nel mockup (eventi: Ora e niente Prezzo; smartbox:
     * Validità e niente Data/N. Persone).
     *
     * @return array<string, string>
     */
    public function columnsFor(string $family): array
    {
        $common = [
            'id' => 'col_id',
            'first_name' => 'col_first_name',
            'last_name' => 'col_last_name',
            'email' => 'col_email',
        ];

        return $common + match ($family) {
            'eventi' => ['title' => 'col_event', 'date' => 'col_date', 'time' => 'col_time', 'people' => 'col_people'],
            'attivita' => ['title' => 'col_activity', 'date' => 'col_date', 'price' => 'col_price', 'people' => 'col_people'],
            'smartbox' => ['title' => 'col_smartbox', 'date' => 'col_validity', 'price' => 'col_price'],
            default => ['title' => 'col_structure', 'date' => 'col_date', 'price' => 'col_price', 'people' => 'col_people'],
        };
    }

    /**
     * Prenotazioni della famiglia: righe ordine dei prodotti del partner,
     * più recenti prima, filtrate da ricerca e data.
     *
     * @return list<array<string, mixed>>
     */
    private function rowsFor(string $family): array
    {
        $rows = $this->itemsFor($family)
            ->map(fn (OrderItem $item): array => $this->row($item, $family))
            ->filter(fn (array $row): bool => $this->passesFilters($row))
            ->values()
            ->all();

        return $rows;
    }

    private function itemsFor(string $family)
    {
        $types = array_map(fn (ProductType $type): string => $type->value, self::TAB_TYPES[$family]);

        return OrderItem::query()
            ->whereIn('product_type', $types)
            ->whereHasMorph(
                'purchasable',
                [Structure::class, Event::class, SmartboxPackage::class],
                fn (Builder $query) => $query->where('user_id', Auth::id()),
            )
            ->with('order')
            ->latest()
            ->get();
    }

    /** @return array<string, mixed> */
    private function row(OrderItem $item, string $family): array
    {
        $order = $item->order;

        return [
            'key' => $item->id,
            'id' => $order->order_number,
            'first_name' => $order->first_name,
            'last_name' => $order->last_name,
            'email' => $order->email,
            'title' => $item->title,
            'date' => $this->dateLabel($item, $family),
            'time' => $family === 'eventi' ? $item->booked_from?->format('H:i') : null,
            'price' => Format::money($item->price_cents),
            'people' => BookingPricingService::persons($item->options ?? []),
            'date_from' => $item->booked_from?->toDateString(),
            'date_to' => $item->booked_until?->toDateString(),
        ];
    }

    /** Eventi: giorno singolo; altrove range check-in/check-out o validità. */
    private function dateLabel(OrderItem $item, string $family): ?string
    {
        if ($item->booked_from === null) {
            return null;
        }

        if ($family === 'eventi' || $item->booked_until === null || $item->booked_from->isSameDay($item->booked_until)) {
            return Format::dateShort($item->booked_from);
        }

        return Format::dateRange($item->booked_from, $item->booked_until);
    }

    /** @param array<string, mixed> $row */
    private function passesFilters(array $row): bool
    {
        $term = mb_strtolower(trim($this->search));

        if ($term !== '' && ! str_contains(mb_strtolower(implode(' ', [
            $row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['title'],
        ])), $term)) {
            return false;
        }

        if ($this->date !== null && $this->date !== '') {
            if ($row['date_from'] === null) {
                return false;
            }

            $day = Carbon::parse($this->date);

            return $day->betweenIncluded(
                Carbon::parse($row['date_from']),
                Carbon::parse($row['date_to'] ?? $row['date_from']),
            );
        }

        return true;
    }
}
