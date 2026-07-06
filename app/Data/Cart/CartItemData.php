<?php

namespace App\Data\Cart;

use App\Models\CartItem\CartItem;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Services\Pricing\BookingPricingService;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * Riga carrello presentata: i dati persistiti (chiave riga, morph, prezzo
 * snapshot in cents, options canonicalizzate) più i campi display idratati dal
 * purchasable a ogni lettura (mai denormalizzati a db: se il prodotto cambia
 * titolo/foto la card si aggiorna da sola). DTO spatie/laravel-data.
 */
final class CartItemData extends Data
{
    /**
     * @param  int|string  $key  id cart_items (auth) o hash md5 (sessione guest)
     * @param  string  $type  alias morph del purchasable (structure/event/smartbox_package)
     * @param  string  $productType  value del ProductType reale del prodotto (chip card)
     * @param  ?array{checkIn: string, checkOut: ?string}  $dates  dd/mm/YYYY; null = riga date assente
     * @param  ?string  $serviceSlot  '17/12/2023, 10:00 - 16:00' (solo famiglia service)
     * @param  ?string  $giftValidity  'Smartbox valida per 12 mesi' (solo smartbox)
     */
    public function __construct(
        public readonly int|string $key,
        public readonly string $type,
        public readonly int $purchasableId,
        public readonly bool $isGift,
        public readonly int $priceCents,
        public readonly array $options,
        public readonly string $title,
        public readonly string $location,
        public readonly string $photoUrl,
        public readonly string $productType,
        public readonly ?array $dates,
        public readonly ?string $serviceSlot,
        public readonly ?string $giftValidity,
    ) {}

    /** Idrata dalla riga cart_items (purchasable eager-loaded e non null). */
    public static function fromModel(CartItem $item): self
    {
        return self::fromParts(
            key: $item->id,
            type: $item->purchasable_type,
            isGift: $item->is_gift,
            priceCents: $item->price_cents,
            options: $item->options ?? [],
            purchasable: $item->purchasable,
        );
    }

    /** Idrata dall'entry di sessione del guest (purchasable già risolto dal caller). */
    public static function fromSessionEntry(string $key, array $entry, Model $purchasable): self
    {
        return self::fromParts(
            key: $key,
            type: $entry['type'],
            isGift: (bool) $entry['is_gift'],
            priceCents: (int) $entry['price_cents'],
            options: $entry['options'] ?? [],
            purchasable: $purchasable,
        );
    }

    /** Costruzione condivisa: deriva i campi display per famiglia di prodotto. */
    private static function fromParts(
        int|string $key,
        string $type,
        bool $isGift,
        int $priceCents,
        array $options,
        Model $purchasable,
    ): self {
        $family = BookingPricingService::family($purchasable);

        return new self(
            key: $key,
            type: $type,
            purchasableId: $purchasable->getKey(),
            isGift: $isGift,
            priceCents: $priceCents,
            options: $options,
            title: $purchasable instanceof Structure ? $purchasable->name : $purchasable->title,
            // La smartbox non ha località: la riga pin mostra l'audience (come la card preferiti).
            location: $purchasable instanceof SmartboxPackage ? $purchasable->audience : $purchasable->location,
            photoUrl: asset('img/xd/'.$purchasable->img.'.jpg'),
            productType: $purchasable->type->value,
            dates: self::dates($family, $options, $purchasable),
            serviceSlot: $family === 'service'
                ? Format::dateShort(CarbonImmutable::parse($options['day'])).', '.$options['time_from'].' - '.$options['time_to']
                : null,
            giftValidity: $purchasable instanceof SmartboxPackage
                ? __('cart.gift_validity', ['validity' => Format::validity($purchasable->validity_months)])
                : null,
        );
    }

    /**
     * Riga date della card per famiglia: structure dalle options, event/activity
     * dalle date reali del prodotto (non editabili), service/smartbox null
     * (il service mostra serviceSlot al posto delle date).
     */
    private static function dates(string $family, array $options, Model $purchasable): ?array
    {
        return match ($family) {
            'structure' => [
                'checkIn' => Format::dateShort(CarbonImmutable::parse($options['check_in'])),
                'checkOut' => Format::dateShort(CarbonImmutable::parse($options['check_out'])),
            ],
            'event' => $purchasable->starts_at !== null
                ? ['checkIn' => Format::dateShort($purchasable->starts_at), 'checkOut' => null]
                : null,
            'activity' => $purchasable->starts_at !== null
                ? [
                    'checkIn' => Format::dateShort($purchasable->starts_at),
                    // Fine derivata: ends_at reale, altrimenti durata (3 giorni = start + 2).
                    'checkOut' => match (true) {
                        $purchasable->ends_at !== null => Format::dateShort($purchasable->ends_at),
                        $purchasable->duration_days !== null && $purchasable->duration_days > 1 => Format::dateShort($purchasable->starts_at->clone()->addDays($purchasable->duration_days - 1)),
                        default => null,
                    },
                ]
                : null,
            default => null,
        };
    }
}
