<?php

namespace App\Services\Admin\Catalog;

use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Support\Format;
use Illuminate\Database\Eloquent\Model;

/**
 * Una scheda del catalogo come la mostrano le schermate del pannello: nome,
 * tipo col suo colore, partner, luogo, prezzo con l'unità, stato.
 */
class CatalogPresenter
{
    /** Badge di stato: etichetta e tono di x-admin.badge. */
    public const STATUS_BADGES = [
        CatalogAdmin::STATUS_PUBLISHED => ['Pubblicata', 'success'],
        CatalogAdmin::STATUS_SUSPENDED => ['Sospesa', 'muted'],
        CatalogAdmin::STATUS_PENDING => ['In attesa', 'warning'],
        CatalogAdmin::STATUS_CHANGES => ['Modifiche chieste', 'pink'],
    ];

    /** Famiglie per il filtro "Tutti i tipi". */
    public const FAMILY_LABELS = [
        'structure' => 'Strutture',
        'event' => 'Attività ed eventi',
        'smartbox_package' => 'Smartbox',
    ];

    public function __construct(private readonly CatalogAdmin $catalog) {}

    /**
     * @return array{family: string, id: int, key: string, name: string, type: string, typeTone: string, partner: string, place: string, region: ?string, price: string, status: string, statusLabel: string, statusTone: string, img: ?string, suspended: bool, url: string}
     */
    public function row(Model $item): array
    {
        $status = $this->catalog->status($item);
        [$statusLabel, $statusTone] = self::STATUS_BADGES[$status];
        $family = $this->catalog->family($item);

        return [
            'family' => $family,
            'id' => (int) $item->getKey(),
            'key' => $family.'-'.$item->getKey(),
            'name' => $this->catalog->name($item),
            'type' => $item->type?->label() ?? self::FAMILY_LABELS[$family],
            'typeTone' => match ($family) {
                'structure' => 'info',
                'event' => 'pink',
                default => 'purple',
            },
            'partner' => $this->catalog->partnerName($item),
            'place' => $this->catalog->place($item),
            'region' => $this->catalog->regionName($item),
            'price' => $this->price($item),
            'status' => $status,
            'statusLabel' => $statusLabel,
            'statusTone' => $statusTone,
            'img' => $item->imageUrl(),
            'suspended' => $item->suspended_at !== null,
            'url' => route('admin.catalog.show', ['type' => $family, 'id' => $item->getKey()]),
        ];
    }

    /** Link alla scheda sul sito, o null se non ha una pagina pubblica raggiungibile. */
    public function publicUrl(Model $item): ?string
    {
        return match (true) {
            $item instanceof Structure && $item->region !== null => $item->type?->value === 'service'
                ? route('holiday.service', ['region' => $item->region->slug, 'service' => $item->slug])
                : route('holiday.structure', ['region' => $item->region->slug, 'structure' => $item->slug]),
            $item instanceof Event => route(
                $item->type?->value === 'event' ? 'eventi.detail' : 'eventi.activity',
                $item->type?->value === 'event' ? ['event' => $item->slug] : ['activity' => $item->slug],
            ),
            $item instanceof SmartboxPackage => route('smartbox.detail', ['box' => $item->slug]),
            default => null,
        };
    }

    public function price(Model $item): string
    {
        if ($item instanceof Event) {
            return $item->is_free || $item->price_cents === null
                ? 'Gratis'
                : Format::money((int) $item->price_cents).' / persona';
        }

        $cents = (int) ($item->price_cents ?? 0);

        if ($cents === 0) {
            return '—';
        }

        return $item instanceof Structure
            ? Format::money($cents).' / notte'
            : Format::money($cents);
    }
}
