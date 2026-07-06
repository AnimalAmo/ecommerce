<?php

namespace App\Livewire;

use App\Models\SmartboxPackage\SmartboxPackage;
use Livewire\Component;

class SmartboxDetail extends Component
{
    /** Slug del cofanetto dalla rotta (es. "relax-lombardia"); il nome differisce dal parametro {box} per non collidere col binding Livewire. */
    public string $boxSlug = '';

    public function mount(string $box): void
    {
        abort_unless(SmartboxPackage::where('slug', $box)->exists(), 404);

        $this->boxSlug = $box;
    }

    public function render()
    {
        $box = SmartboxPackage::where('slug', $this->boxSlug)->firstOrFail();

        return view('livewire.smartbox-detail', [
            'box' => $box,
            'hotelServices' => $box->amenityRows('hotel'),
            'animalServices' => $box->amenityRows('animal'),
        ])->title('AnimalAmo — '.$box->title);
    }
}
