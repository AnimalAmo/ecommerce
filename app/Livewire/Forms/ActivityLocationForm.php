<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Attività/Eventi — step 3 "Luogo". Indirizzo dell'evento + punto d'incontro.
 */
class ActivityLocationForm extends Form
{
    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    /** Punto d'incontro, localizzato: it obbligatorio, en opzionale. */
    public array $meetingPoint = ['it' => '', 'en' => ''];

    /**
     * `province.exists`: la sigla deve esistere in `provinces`, lo stesso
     * controllo che il pannello admin fa già (ActivityCreate). Un'attività non
     * ha una regione — EventPublisher scrive un Venue, non `region_id` — ma la
     * sigla finisce testuale nell'etichetta del luogo e nell'indirizzo del
     * Venue: una sigla inventata diventa un «Garda (ZZ)» a catalogo, sotto gli
     * occhi del cliente e non più modificabile dal partner a scheda pubblicata.
     *
     * Volutamente SENZA un `messages()` su questo Form: ActivityCreate
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
            'meetingPoint.it' => ['required', 'string', 'max:128'],
            'meetingPoint.en' => ['nullable', 'string', 'max:128'],
        ];
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->address = $draft->address ?? '';
        $this->city = $draft->city ?? '';
        $this->province = $draft->province ?? '';
        $this->zip = $draft->zip ?? '';
        $this->meetingPoint = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('meeting_point'));
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip' => $this->zip,
            // Le traduzioni vuote non vengono salvate: su EN scatta il fallback IT.
            'meeting_point' => array_filter($this->meetingPoint, fn ($value) => filled($value)),
        ];
    }
}
