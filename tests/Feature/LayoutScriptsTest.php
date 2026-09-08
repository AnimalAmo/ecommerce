<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutScriptsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Widget Iubenda (privacy e cookie): senza di lui il sito va online senza
     * informativa, quindi la sua presenza è un test, non una convenzione.
     */
    public function test_every_page_carries_the_iubenda_widget(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://embeds.iubenda.com/widgets/f982b4fa-cef8-48b4-86c6-a9d12f07b263.js', false);
    }
}
