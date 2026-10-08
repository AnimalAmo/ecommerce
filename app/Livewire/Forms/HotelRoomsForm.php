<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Struttura (hotel) — step 5 "Stanze". Ogni riga è una stanza con la sua
 * mini-scheda (nome, tipologia, descrizione, foto, prezzo, ospiti, animali,
 * unità, servizi camera), nel formato di StructureDraft::normalizedRooms():
 * StructurePublisher ne fa una riga `rooms` agganciata a `key`.
 *
 * La casa vacanza si affitta intera: stessa colonna `rooms`, ma una riga sola
 * con tipologia fittizia, `units` bloccato a 1 e gli ospiti chiesti come
 * "posti letto" (copiati anche in `beds`, che le letture legacy conoscono).
 */
class HotelRoomsForm extends Form
{
    /** Tipologia fittizia della riga unica in modalità alloggio intero. */
    public const WHOLE_PROPERTY_TYPE = 'intera_struttura';

    /** Foto massime per stanza. */
    public const MAX_PHOTOS = 10;

    /** Righe stanza nel formato di normalizedRooms(). */
    public array $rooms = [];

    /** Vero per le case vacanza: lo step diventa "l'alloggio", non "le stanze". */
    public bool $wholeProperty = false;

    public string $checkinFrom = '';

    public string $checkinTo = '';

    public string $checkoutFrom = '';

    public string $checkoutTo = '';

    public function rules(): array
    {
        $rules = [
            'rooms' => ['required', 'array', $this->wholeProperty ? 'size:1' : 'min:1'],
        ];

        foreach ($this->roomRules() as $field => $rule) {
            $rules['rooms.*.'.$field] = $rule;
        }

        return $rules + [
            'checkinFrom' => ['required', 'string'],
            'checkinTo' => ['required', 'string'],
            'checkoutFrom' => ['required', 'string'],
            'checkoutTo' => ['required', 'string'],
        ];
    }

    /**
     * Regole di UNA stanza, senza prefisso: le usa rules() per tutte le righe
     * e la modale per la riga che si sta modificando (`roomForm.*`).
     *
     * @return array<string, mixed>
     */
    public function roomRules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(ServiceOptionLabels::slugs($this->wholeProperty ? 'room_type_whole' : 'room_type'))],
            'name.it' => ['nullable', 'string', 'max:80'],
            'name.en' => ['nullable', 'string', 'max:80'],
            'description.it' => ['nullable', 'string', 'max:1000'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            // decimal:0,2 + max: il publisher converte in cents (unsignedInteger),
            // '1.500' ambiguo e importi a 9+ cifre overflowerebbero la colonna.
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            // L'alloggio intero conta i posti letto, che prima arrivavano a 50.
            'max_guests' => ['required', 'integer', 'min:1', $this->wholeProperty ? 'max:50' : 'max:20'],
            'max_animals' => ['required', 'integer', 'min:0', 'max:10'],
            // L'alloggio intero è una sola unità: non si chiede, si valida che resti 1.
            'units' => $this->wholeProperty ? ['required', 'integer', 'in:1'] : ['required', 'integer', 'min:1'],
            'photos' => ['array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['string'],
            'amenities' => ['array'],
            'amenities.*' => ['string', Rule::in(ServiceOptionLabels::slugs('services'))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'rooms.required' => __('partner.hotel_rooms.error_no_rooms'),
            'rooms.min' => __('partner.hotel_rooms.error_no_rooms'),
        ] + ($this->wholeProperty
            ? ['rooms.*.max_guests.required' => __('partner.hotel_rooms.beds_error')]
            : []);
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->wholeProperty = $draft->type === 'casa_vacanza';
        $this->rooms = array_map(fn (array $row): array => $this->withDefaults($row), $draft->normalizedRooms());

        // L'alloggio intero ha sempre la sua card: il partner la apre e la compila.
        if ($this->wholeProperty && $this->rooms === []) {
            $this->rooms = [$this->blankRow()];
        }

        $this->checkinFrom = $draft->checkin_from ?? '';
        $this->checkinTo = $draft->checkin_to ?? '';
        $this->checkoutFrom = $draft->checkout_from ?? '';
        $this->checkoutTo = $draft->checkout_to ?? '';
    }

    /**
     * Stanza vuota di partenza, con la sua chiave già assegnata: è quella che
     * il publisher usa per tenere stabile l'id della stanza a catalogo.
     *
     * @return array<string, mixed>
     */
    public function blankRow(): array
    {
        return [
            'key' => (string) Str::uuid(),
            'type' => $this->wholeProperty ? self::WHOLE_PROPERTY_TYPE : '',
            'name' => ['it' => '', 'en' => ''],
            'description' => ['it' => '', 'en' => ''],
            'price' => '',
            'max_guests' => $this->wholeProperty ? '' : 2,
            'max_animals' => 1,
            'units' => 1,
            'photos' => [],
            'amenities' => [],
        ];
    }

    /**
     * Riga pronta per la bozza: tipi giusti, chiave sempre presente, e per
     * l'alloggio intero tipologia e unità forzate (il client non le sceglie).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function cleanRow(array $row): array
    {
        $row = $this->withDefaults($row);

        $clean = [
            'key' => filled($row['key']) ? (string) $row['key'] : (string) Str::uuid(),
            'type' => (string) $row['type'],
            'name' => ['it' => (string) ($row['name']['it'] ?? ''), 'en' => (string) ($row['name']['en'] ?? '')],
            'description' => ['it' => (string) ($row['description']['it'] ?? ''), 'en' => (string) ($row['description']['en'] ?? '')],
            'price' => (string) $row['price'],
            'max_guests' => (int) $row['max_guests'],
            'max_animals' => (int) $row['max_animals'],
            'units' => (int) $row['units'],
            'photos' => array_values(array_unique(array_map('strval', $row['photos']))),
            'amenities' => array_values(array_unique(array_map('strval', $row['amenities']))),
        ];

        if ($this->wholeProperty) {
            $clean['type'] = self::WHOLE_PROPERTY_TYPE;
            $clean['units'] = 1;
            $clean['beds'] = $clean['max_guests'];
        }

        return $clean;
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'rooms' => array_map(fn (array $row): array => $this->cleanRow($row), array_values($this->rooms)),
            'checkin_from' => $this->checkinFrom,
            'checkin_to' => $this->checkinTo,
            'checkout_from' => $this->checkoutFrom,
            'checkout_to' => $this->checkoutTo,
        ];
    }

    /**
     * Completa una riga coi campi mancanti (testi con entrambe le lingue, per
     * i wire:model della modale).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function withDefaults(array $row): array
    {
        $blank = $this->blankRow();
        $blank['key'] = '';

        $row = array_merge($blank, $row);
        $row['name'] = array_merge(['it' => '', 'en' => ''], is_array($row['name']) ? $row['name'] : []);
        $row['description'] = array_merge(['it' => '', 'en' => ''], is_array($row['description']) ? $row['description'] : []);
        $row['photos'] = is_array($row['photos']) ? array_values($row['photos']) : [];
        $row['amenities'] = is_array($row['amenities']) ? array_values($row['amenities']) : [];

        return $row;
    }
}
