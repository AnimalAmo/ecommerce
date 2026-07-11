<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Struttura (hotel) — step 5 "Stanze". Righe stanza ripetibili (tipologia,
 * numero, prezzo) + fasce orarie di check-in/check-out.
 */
class HotelRoomsForm extends Form
{
    /** Righe stanza (ripetibili con "Aggiungi stanze"): tipologia, numero, prezzo. */
    public array $rooms = [
        ['type' => '', 'count' => 0, 'price' => ''],
    ];

    public string $checkinFrom = '';

    public string $checkinTo = '';

    public string $checkoutFrom = '';

    public string $checkoutTo = '';

    public function rules(): array
    {
        return [
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.type' => ['required', 'string'],
            'rooms.*.count' => ['required', 'integer', 'min:1'],
            // decimal:0,2 + max: il publisher converte in cents (unsignedInteger),
            // '1.500' ambiguo e importi a 9+ cifre overflowerebbero la colonna.
            'rooms.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'checkinFrom' => ['required', 'string'],
            'checkinTo' => ['required', 'string'],
            'checkoutFrom' => ['required', 'string'],
            'checkoutTo' => ['required', 'string'],
        ];
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->rooms = $draft->rooms ?: [['type' => '', 'count' => 0, 'price' => '']];
        $this->checkinFrom = $draft->checkin_from ?? '';
        $this->checkinTo = $draft->checkin_to ?? '';
        $this->checkoutFrom = $draft->checkout_from ?? '';
        $this->checkoutTo = $draft->checkout_to ?? '';
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'rooms' => $this->rooms,
            'checkin_from' => $this->checkinFrom,
            'checkin_to' => $this->checkinTo,
            'checkout_from' => $this->checkoutFrom,
            'checkout_to' => $this->checkoutTo,
        ];
    }
}
