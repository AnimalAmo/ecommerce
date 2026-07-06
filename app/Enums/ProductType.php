<?php

namespace App\Enums;

/**
 * Tassonomia unica delle tipologie prodotto (decisione ratificata #3, docs/analisi-entita-dinamiche.md).
 *
 * I colori arrivano dal design XD e non cambiano a runtime; le label sono traduzioni
 * (sito multilingua) in lang/{locale}/product-type.php. Il plurale esiste solo dove
 * il design lo usa (chip community): stay/wellness/adventure restano invariati in it.
 */
enum ProductType: string
{
    case Structure = 'structure';
    case Service = 'service';
    case Activity = 'activity';
    case Event = 'event';
    case Stay = 'stay';
    case Wellness = 'wellness';
    case Adventure = 'adventure';

    public function label(): string
    {
        return __('product-type.'.$this->value.'.singular');
    }

    public function labelPlural(): string
    {
        return __('product-type.'.$this->value.'.plural');
    }

    /** Colore hex della chip (Identità Cromatica XD). */
    public function color(): string
    {
        return match ($this) {
            self::Structure => '#FF9F3E',
            self::Service => '#FDC220',
            self::Activity => '#8E53E6',
            self::Event => '#C59FFD',
            self::Stay => '#8DE0FF',
            self::Wellness => '#8DABFF',
            self::Adventure => '#3E72FF',
        };
    }
}
