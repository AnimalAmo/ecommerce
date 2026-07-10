<?php

namespace App\Livewire\Partner;

use Livewire\Component;

class HotelRooms extends Component
{
    /** Righe stanza (ripetibili con "Aggiungi stanze"): tipologia, numero, prezzo. */
    public array $rooms = [
        ['type' => '', 'count' => 0, 'price' => ''],
    ];

    public string $checkinFrom = '';

    public string $checkinTo = '';

    public string $checkoutFrom = '';

    public string $checkoutTo = '';

    public function addRoom(): void
    {
        $this->rooms[] = ['type' => '', 'count' => 0, 'price' => ''];
    }

    public function incrementRoom(int $i): void
    {
        if (isset($this->rooms[$i])) {
            $this->rooms[$i]['count']++;
        }
    }

    public function decrementRoom(int $i): void
    {
        if (isset($this->rooms[$i]) && $this->rooms[$i]['count'] > 0) {
            $this->rooms[$i]['count']--;
        }
    }

    /** Orari selezionabili (mezz'ora): 00:00 → 23:30. */
    public function times(): array
    {
        $times = [];
        for ($h = 0; $h < 24; $h++) {
            $times[] = sprintf('%02d:00', $h);
            $times[] = sprintf('%02d:30', $h);
        }

        return $times;
    }

    public function next(): void
    {
        $this->validate([
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.type' => ['required', 'string'],
            'rooms.*.count' => ['required', 'integer', 'min:1'],
            'rooms.*.price' => ['required', 'numeric', 'min:0'],
            'checkinFrom' => ['required', 'string'],
            'checkinTo' => ['required', 'string'],
            'checkoutFrom' => ['required', 'string'],
            'checkoutTo' => ['required', 'string'],
        ]);

        // TODO: advance to step 6 of 11 of the structure creation flow.
    }

    public function render()
    {
        return view('livewire.partner.hotel-rooms', ['times' => $this->times()])
            ->title(__('partner.hotel_rooms.title'));
    }
}
