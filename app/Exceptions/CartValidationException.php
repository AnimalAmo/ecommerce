<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violazione delle regole carrello (disponibilità/acquistabilità): il messaggio
 * è già tradotto alla costruzione (lang/it/cart.php) e i componenti Livewire lo
 * mostrano con Flux::toast(variant: 'danger').
 */
class CartValidationException extends RuntimeException
{
    /** Una data dell'intervallo è chiusa (structure). */
    public static function unavailableDates(): self
    {
        return new self(__('cart.unavailable_dates'));
    }

    /** Il giorno scelto è chiuso (service). */
    public static function unavailableDay(): self
    {
        return new self(__('cart.unavailable_day'));
    }

    /** Data nel passato (check-in, giorno servizio, evento già iniziato). */
    public static function pastDate(): self
    {
        return new self(__('cart.past_date'));
    }

    /** Check-out non successivo al check-in. */
    public static function invalidRange(): self
    {
        return new self(__('cart.invalid_range'));
    }

    /** Orario di fine non successivo all'inizio (service). */
    public static function invalidTimes(): self
    {
        return new self(__('cart.invalid_times'));
    }

    /** Partecipanti oltre la capienza massima (event/activity). */
    public static function soldOut(): self
    {
        return new self(__('cart.sold_out'));
    }

    /** Prodotto non acquistabile (evento gratuito/senza prezzo: solo Partecipa). */
    public static function notPurchasable(): self
    {
        return new self(__('cart.not_purchasable'));
    }
}
