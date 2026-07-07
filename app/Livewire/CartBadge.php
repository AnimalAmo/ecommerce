<?php

namespace App\Livewire;

use App\Services\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Icona carrello dell'header con badge contatore: si aggiorna in tempo reale
 * quando un componente della pagina modifica il carrello (evento 'cart-updated').
 */
class CartBadge extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Il re-render rilegge il conteggio dal CartManager.
    }

    public function render(): View
    {
        return view('livewire.cart-badge', [
            'count' => app(CartManager::class)->count(),
        ]);
    }
}
