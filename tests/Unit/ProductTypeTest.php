<?php

namespace Tests\Unit;

use App\Enums\ProductType;
use Tests\TestCase;

class ProductTypeTest extends TestCase
{
    public function test_labels_and_colors_match_the_xd_design(): void
    {
        app()->setLocale('it');

        $expected = [
            ['case' => ProductType::Structure, 'label' => 'Struttura', 'plural' => 'Strutture', 'color' => '#FF9F3E'],
            ['case' => ProductType::Service, 'label' => 'Servizio', 'plural' => 'Servizi', 'color' => '#FDC220'],
            ['case' => ProductType::Activity, 'label' => 'Attività', 'plural' => 'Attività', 'color' => '#8E53E6'],
            ['case' => ProductType::Event, 'label' => 'Evento', 'plural' => 'Eventi', 'color' => '#C59FFD'],
            ['case' => ProductType::Stay, 'label' => 'Soggiorno', 'plural' => 'Soggiorno', 'color' => '#8DE0FF'],
            ['case' => ProductType::Wellness, 'label' => 'Benessere', 'plural' => 'Benessere', 'color' => '#8DABFF'],
            ['case' => ProductType::Adventure, 'label' => 'Avventura', 'plural' => 'Avventura', 'color' => '#3E72FF'],
        ];

        $this->assertCount(count($expected), ProductType::cases());

        foreach ($expected as $row) {
            $this->assertSame($row['label'], $row['case']->label());
            $this->assertSame($row['plural'], $row['case']->labelPlural());
            $this->assertSame($row['color'], $row['case']->color());
        }
    }
}
