<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Illuminate\Validation\Rule;
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

    /**
     * `province.exists`: la sigla deve esistere in `provinces`. Senza questo
     * controllo una sigla fuori elenco veniva salvata e la pubblicazione ne
     * ricavava `region_id` NULL, con la scheda invisibile su ogni pagina
     * regione (si veda StructurePublisher::regionIdFor()).
     *
     * Volutamente SENZA un `messages()` su questo Form: StructureCreate
     * costruisce il proprio messaggio `location.province.exists` e poi ci fonde
     * sopra quelli dei Form object, quindi una chiave qui vincerebbe sul
     * messaggio del pannello admin. Nel wizard resta il generico di
     * `lang/it/validation.php` e il suo gemello inglese, che basta: il campo è una select ricercabile
     * (components/partner/province-select) che manda sempre una sigla del DB,
     * quindi l'errore lo vede solo chi forgia la richiesta.
     */
    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64', Rule::exists('provinces', 'short_name')],
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
