<?php

namespace App\Livewire\Content;

use Livewire\Component;

class AboutUs extends Component
{
    public function render()
    {
        return view('livewire.content.about-us')->title(__('about.page_title'));
    }
}
