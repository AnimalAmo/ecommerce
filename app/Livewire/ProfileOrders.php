<?php

namespace App\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;

class ProfileOrders extends Component
{
    /** Tab attiva (deep-link ?tab=passati come la community): artboard XD "– 1" = stato Passati. */
    #[Url(as: 'tab', except: 'programma')]
    public string $tab = 'programma';

    public const TABS = ['programma' => 'In programma', 'passati' => 'Passati'];

    /**
     * Ordini mock come da XD "Profilo – i miei ordini" (In programma) e "– 1" (Passati):
     * data, numero articoli, totale e striscia di miniature.
     */
    // TODO: ordini reali da backend + pagina riepilogo ordine (artboard "Profilo – i miei ordini – riepilogo")
    public const ORDERS = [
        'programma' => [
            [
                'id' => 'ord-476',
                'date' => '17/01/2024',
                'items' => 3,
                'price' => 476,
                'photos' => ['cart-hotel-brescia.jpg', 'cart-weekend-piemonte.jpg', 'cart-excursions-viareggio.jpg'],
            ],
            [
                'id' => 'ord-312',
                'date' => '04/10/2023',
                'items' => 2,
                'price' => 312,
                'photos' => ['order-programma-2a.jpg', 'order-programma-2b.jpg'],
            ],
            [
                'id' => 'ord-83',
                'date' => '10/02/2023',
                'items' => 1,
                'price' => 83,
                'photos' => ['event-puppy-yoga.jpg'],
            ],
        ],
        'passati' => [
            [
                'id' => 'ord-345',
                'date' => '22/06/2023',
                'items' => 2,
                'price' => 345,
                'photos' => ['order-passati-1a.jpg', 'order-passati-1b.jpg'],
            ],
        ],
    ];

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    /** Etichetta articoli ("1 articolo" / "N articoli"). */
    public function itemsLabel(int $items): string
    {
        return $items === 1 ? '1 articolo' : $items.' articoli';
    }

    public function render()
    {
        return view('livewire.profile-orders', [
            'tabs' => self::TABS,
            'orders' => self::ORDERS[$this->tab],
        ])->title('I miei ordini — AnimalAmo');
    }
}
