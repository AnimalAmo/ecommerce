<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Livewire\Form;

/**
 * Struttura (hotel) — step 3 "Luogo". Indirizzo + licenza/autorizzazione.
 */
class HotelLocationForm extends Form
{
    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    public string $license = '';

    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
            'zip' => ['required', 'digits:5'],
            'license' => ['required', 'string', 'max:64'],
        ];
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->address = $draft->address ?? '';
        $this->city = $draft->city ?? '';
        $this->province = $draft->province ?? '';
        $this->zip = $draft->zip ?? '';
        $this->license = $draft->license ?? '';
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip' => $this->zip,
            'license' => $this->license,
        ];
    }
}
