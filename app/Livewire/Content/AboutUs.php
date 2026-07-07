<?php

namespace App\Livewire\Content;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Chi siamo — AnimalAmo')]
class AboutUs extends Component
{
    /**
     * Testo lorem verbatim dell'XD: usato due volte come paragrafo in "Chi siamo"
     * e una volta sotto ciascuno dei due blocchi "Vivi il tuo viaggio" / "Trova le migliori avventure".
     */
    public const LOREM = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.';

    public function render()
    {
        return view('livewire.content.about-us', ['lorem' => self::LOREM]);
    }
}
