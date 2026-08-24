<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Struttura (hotel) — step 5 "Stanze". Righe stanza ripetibili (tipologia,
 * numero, prezzo) + fasce orarie di check-in/check-out.
 *
 * La casa vacanza si affitta intera: stessa colonna `rooms` (JSON), ma una
 * riga sola, `count` bloccato a 1 e `beds` al posto della tipologia. Restare
 * dentro `rooms` tiene invariati publisher e completeness check della bozza
 * (StructurePublisher legge rooms[].price, DraftPublisher fa filled(rooms)).
 */
class HotelRoomsForm extends Form
{
    /** Tipologia fittizia della riga unica in modalità alloggio intero. */
    public const WHOLE_PROPERTY_TYPE = 'intera_struttura';

    /** Righe stanza (ripetibili con "Aggiungi stanze"): tipologia, numero, prezzo. */
    public array $rooms = [
        ['type' => '', 'count' => 0, 'price' => ''],
    ];

    /** Vero per le case vacanza: lo step diventa "l'alloggio", non "le stanze". */
    public bool $wholeProperty = false;

    public string $checkinFrom = '';

    public string $checkinTo = '';

    public string $checkoutFrom = '';

    public string $checkoutTo = '';

    public function rules(): array
    {
        return [
            'rooms' => ['required', 'array', $this->wholeProperty ? 'size:1' : 'min:1'],
            'rooms.*.type' => ['required', 'string'],
            // L'alloggio intero è una sola unità: il numero non è una scelta del
            // partner, quindi non gli si chiede — si valida che resti 1.
            'rooms.*.count' => $this->wholeProperty
                ? ['required', 'integer', 'in:1']
                : ['required', 'integer', 'min:1'],
            // decimal:0,2 + max: il publisher converte in cents (unsignedInteger),
            // '1.500' ambiguo e importi a 9+ cifre overflowerebbero la colonna.
            'rooms.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'checkinFrom' => ['required', 'string'],
            'checkinTo' => ['required', 'string'],
            'checkoutFrom' => ['required', 'string'],
            'checkoutTo' => ['required', 'string'],
        ] + ($this->wholeProperty
            ? ['rooms.*.beds' => ['required', 'integer', 'min:1', 'max:50']]
            : []);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->wholeProperty
            ? ['rooms.*.beds.required' => __('partner.hotel_rooms.beds_error')]
            : [];
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->wholeProperty = $draft->type === 'casa_vacanza';
        $this->rooms = $draft->rooms ?: [$this->blankRow()];
        $this->checkinFrom = $draft->checkin_from ?? '';
        $this->checkinTo = $draft->checkin_to ?? '';
        $this->checkoutFrom = $draft->checkout_from ?? '';
        $this->checkoutTo = $draft->checkout_to ?? '';
    }

    /**
     * Riga vuota di partenza. Per l'alloggio intero tipologia e numero sono
     * già decisi: il partner compila solo posti letto e prezzo.
     *
     * @return array<string, mixed>
     */
    public function blankRow(): array
    {
        return $this->wholeProperty
            ? ['type' => self::WHOLE_PROPERTY_TYPE, 'count' => 1, 'beds' => '', 'price' => '']
            : ['type' => '', 'count' => 0, 'price' => ''];
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
