<?php

namespace App\Services\Pricing;

use App\Enums\ProductType;
use App\Exceptions\CartValidationException;
use App\Models\Event\Event;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Quotazione server-side delle prenotazioni (mai prezzi dal client): integer
 * cents dai seed, formule per famiglia (decisioni ratificate step 3). Il
 * supplemento animali seed è 0, quindi i totali restano quelli dell'XD.
 */
class BookingPricingService
{
    /**
     * Totale riga in cents per il purchasable con le options canonicalizzate.
     *
     * @throws CartValidationException evento gratuito/senza prezzo (solo Partecipa)
     */
    public function quote(object $purchasable, array $options): int
    {
        return match (self::family($purchasable)) {
            'structure' => $this->structureQuote($purchasable, $options),
            'service' => $this->serviceQuote($purchasable, $options),
            'event', 'activity' => $this->eventQuote($purchasable, $options),
            // Prezzo flat del cofanetto: gli animali non sono prezzati.
            'smartbox' => $purchasable->price_cents,
        };
    }

    /**
     * Famiglia di pricing/availability del purchasable: il modello Structure
     * copre structure e service, Event copre event e activity (discriminati dal
     * ProductType della riga). Unica implementazione, riusata da availability e DTO.
     *
     * @return 'structure'|'service'|'event'|'activity'|'smartbox'
     */
    public static function family(object $purchasable): string
    {
        return match (true) {
            $purchasable instanceof Structure => $purchasable->type === ProductType::Service ? 'service' : 'structure',
            $purchasable instanceof Event => $purchasable->type === ProductType::Activity ? 'activity' : 'event',
            $purchasable instanceof SmartboxPackage => 'smartbox',
            default => throw new InvalidArgumentException('Purchasable non supportato: '.$purchasable::class),
        };
    }

    /** Notte per notte: prezzo camera + supplemento per animale per notte. */
    private function structureQuote(Structure $structure, array $options): int
    {
        $nights = self::nights($options);

        return $structure->price_cents * $nights
            + $structure->animal_supplement_cents * self::animals($options) * $nights;
    }

    /** A ore (frazioni per eccesso): prezzo orario + supplemento per animale per ora. */
    private function serviceQuote(Structure $service, array $options): int
    {
        $hours = self::hours($options);

        return $service->price_cents * $hours
            + $service->animal_supplement_cents * self::animals($options) * $hours;
    }

    /** A persona: participants (evento, sempre 1 dalla pagina) o somma ospiti (attività). */
    private function eventQuote(Event $event, array $options): int
    {
        // Gratis/senza prezzo = CTA Partecipa: mai nel carrello (niente fallback).
        if ($event->hasJoinCta()) {
            throw CartValidationException::notPurchasable();
        }

        return $event->price_cents * self::persons($options);
    }

    /** Numero animali: somma dei count di options['animals'] ({specie: count}). */
    private static function animals(array $options): int
    {
        return (int) array_sum($options['animals'] ?? []);
    }

    /** Notti dell'intervallo check_in/check_out (minimo 1). */
    private static function nights(array $options): int
    {
        $checkIn = CarbonImmutable::parse($options['check_in']);
        $checkOut = CarbonImmutable::parse($options['check_out']);

        return max(1, (int) $checkIn->diffInDays($checkOut));
    }

    /** Ore dell'intervallo time_from/time_to (frazioni per eccesso, minimo 1). */
    private static function hours(array $options): int
    {
        $from = CarbonImmutable::parse($options['day'].' '.$options['time_from']);
        $to = CarbonImmutable::parse($options['day'].' '.$options['time_to']);

        return max(1, (int) ceil($from->diffInMinutes($to) / 60));
    }

    /**
     * Persone: participants (evento) o somma ospiti (attività). Helper
     * condiviso: stessa aritmetica per pricing, availability (capienza) e
     * ReserveAvailabilityPipe (consumo posti) — UNA implementazione.
     */
    public static function persons(array $options): int
    {
        return (int) ($options['participants'] ?? array_sum($options['guests'] ?? []));
    }
}
