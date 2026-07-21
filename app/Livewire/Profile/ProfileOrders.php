<?php

namespace App\Livewire\Profile;

use App\Services\Orders\OrderQueryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProfileOrders extends Component
{
    /** Tab attiva (deep-link ?tab=passati come la community): artboard XD "– 1" = stato Passati. */
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

    public function render(OrderQueryService $orders)
    {
        $this->normalizeTab();

        return view('livewire.profile.profile-orders', [
            'tabs' => array_map(fn ($key) => __($key), self::TABS),
            // Bucket derivato nel service: passato ⇔ max(items.booked_until) < now().
            'orders' => $orders->listFor(Auth::user(), $this->tab === 'passati'),
        ])->title(__('profile.title_orders'));
    }
}
