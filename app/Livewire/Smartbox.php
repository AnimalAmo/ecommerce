<?php

namespace App\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('AnimalAmo — Smartbox')]
class Smartbox extends Component
{
    /**
     * Cofanetti campione in ordine di griglia XD (riga per riga).
     * Tag: Soggiorno / Benessere / Avventura; prezzo placeholder "0,00 €" ovunque.
     * Slug derivato da 'img' senza il prefisso "smartbox-".
     */
    public const BOXES = [
        ['title' => 'Weekend di relax in Lombardia', 'slug' => 'relax-lombardia', 'tag' => 'Soggiorno', 'audience' => 'Coppia', 'img' => 'smartbox-relax-lombardia'],
        ['title' => 'Weekend in Piemonte', 'slug' => 'piemonte', 'tag' => 'Soggiorno', 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-piemonte'],
        ['title' => 'Weekend di relax in Sardegna', 'slug' => 'relax-sardegna', 'tag' => 'Benessere', 'audience' => 'Coppia', 'img' => 'smartbox-relax-sardegna'],
        ['title' => 'Weekend in Liguria', 'slug' => 'liguria', 'tag' => 'Avventura', 'audience' => 'Coppia', 'img' => 'smartbox-liguria'],
        ['title' => '1 settimana di relax in Sardegna', 'slug' => 'settimana-relax-sardegna', 'tag' => 'Soggiorno', 'audience' => 'Coppia', 'img' => 'smartbox-settimana-relax-sardegna'],
        ['title' => '4 giorni al lago', 'slug' => 'lago', 'tag' => 'Avventura', 'audience' => 'Famiglia', 'img' => 'smartbox-lago'],
        ['title' => '1 settimana di avventure', 'slug' => 'avventure', 'tag' => 'Benessere', 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-avventure'],
        ['title' => '1 settimana in Sardegna', 'slug' => 'settimana-sardegna', 'tag' => 'Avventura', 'audience' => 'Famiglia', 'img' => 'smartbox-settimana-sardegna'],
        ['title' => 'Weekend di relax in Lombardia', 'slug' => 'relax-lombardia-2', 'tag' => 'Benessere', 'audience' => 'Coppia', 'img' => 'smartbox-relax-lombardia-2'],
        ['title' => 'Weekend sugli sci', 'slug' => 'sci', 'tag' => 'Avventura', 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-sci'],
        ['title' => 'Settimana bianca', 'slug' => 'bianca', 'tag' => 'Avventura', 'audience' => 'Famiglia', 'img' => 'smartbox-bianca'],
        ['title' => 'Weekend nella capitale', 'slug' => 'capitale', 'tag' => 'Soggiorno', 'audience' => 'Gruppo (+5 persone)', 'img' => 'smartbox-capitale'],
    ];

    public function render()
    {
        return view('livewire.smartbox', ['boxes' => self::BOXES]);
    }
}
