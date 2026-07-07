<?php

namespace App\Livewire;

use App\Services\Orders\OrderQueryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProfileOrders extends Component
{
    /** Tab attiva (deep-link ?tab=passati come la community): artboard XD "– 1" = stato Passati. */
    #[Url(as: 'tab', except: 'programma')]
    public string $tab = 'programma';

    public const TABS = ['programma' => 'In programma', 'passati' => 'Passati'];

    public function mount(): void
    {
        $this->normalizeTab();
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    /** Il binding #[Url] accetta qualunque ?tab=…: fuori whitelist → 'programma'. */
    private function normalizeTab(): void
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'programma';
        }
    }

    public function render(OrderQueryService $orders)
    {
        $this->normalizeTab();

        return view('livewire.profile-orders', [
            'tabs' => self::TABS,
            // Bucket derivato nel service: passato ⇔ max(items.booked_until) < now().
            'orders' => $orders->listFor(Auth::user(), $this->tab === 'passati'),
        ])->title('I miei ordini — AnimalAmo');
    }
}
