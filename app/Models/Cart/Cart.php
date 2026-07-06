<?php

namespace App\Models\Cart;

use App\Models\Cart\Concerns\CartHasRelationships;
use Database\Factories\Cart\CartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Carrello persistente dell'utente autenticato (uno a testa, user_id unique).
 * Il guest non passa da qui: vive in sessione (SessionCartStorage).
 * ATTENZIONE collisione con App\Livewire\Cart: import sempre espliciti (alias CartModel).
 */
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use CartHasRelationships, HasFactory;

    protected $fillable = [
        'user_id',
    ];
}
