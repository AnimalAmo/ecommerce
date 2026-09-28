<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use App\Support\Translations;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Attività/Eventi — step 3 "Luogo". L'indirizzo vale per tutti; il campo
 * accanto cambia col ramo (risposta della cliente, 27/09/2026): un evento ha un
 * punto d'incontro, un professionista non ha un ritrovo ma una zona in cui
 * lavora. Prima `meetingPoint.it` era obbligatorio per tutti e un dog sitter
 * restava bloccato su questo step.
 */
class ActivityLocationForm extends Form
{
    public string $address = '';

    public string $city = '';

    public string $province = '';

    public string $zip = '';

    /** Punto d'incontro, localizzato: solo Eventi, it obbligatorio, en opzionale. */
    public array $meetingPoint = ['it' => '', 'en' => ''];

    /** Zona operativa, localizzata: solo Attività, facoltativa in entrambe le lingue. */
    public array $operatingArea = ['it' => '', 'en' => ''];

    /** Flag di controllo, non persistito: stesso idioma di ActivityInfoForm. */
    public bool $isEvent = false;

    /**
     * `province.exists`: la sigla deve esistere in `provinces`, lo stesso
     * controllo che il pannello admin fa già (ActivityCreate). Un'attività non
     * ha una regione — EventPublisher scrive un Venue, non `region_id` — ma la
     * sigla finisce testuale nell'etichetta del luogo e nell'indirizzo del
     * Venue: una sigla inventata diventa un «Garda (ZZ)» a catalogo, sotto gli
     * occhi del cliente e non più modificabile dal partner a scheda pubblicata.
     *
     * Due code separate e non un `required_if`: il campo del ramo abbandonato
     * non viene nemmeno disegnato, quindi non deve nemmeno essere validato —
     * altrimenti un valore rimasto in sessione da prima del cambio di ramo
     * bloccherebbe uno step che non lo mostra più.
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
        $rules = [
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64', Rule::exists('provinces', 'short_name')],
            'zip' => ['required', 'digits:5'],
        ];

        if ($this->isEvent) {
            $rules['meetingPoint.it'] = ['required', 'string', 'max:128'];
            $rules['meetingPoint.en'] = ['nullable', 'string', 'max:128'];

            return $rules;
        }

        $rules['operatingArea.it'] = ['nullable', 'string', 'max:128'];
        $rules['operatingArea.en'] = ['nullable', 'string', 'max:128'];

        return $rules;
    }

    public function setFromDraft(StructureDraft $draft): void
    {
        $this->address = $draft->address ?? '';
        $this->city = $draft->city ?? '';
        $this->province = $draft->province ?? '';
        $this->zip = $draft->zip ?? '';
        $this->meetingPoint = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('meeting_point'));
        $this->operatingArea = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('operating_area'));
        $this->isEvent = $draft->type === 'eventi';
    }

    /**
     * Attributi nel formato colonne della bozza (snake_case). Si scrive solo la
     * colonna del ramo corrente: quella dell'altro la azzera ActivityType al
     * cambio di tipo, non questo Form, che di un ramo che non mostra non sa
     * niente.
     */
    public function toDraft(): array
    {
        $attributes = [
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'zip' => $this->zip,
        ];

        // Una lingua lasciata vuota va a null, non viene fatta cadere: su EN
        // scatta il fallback IT anche se prima c'era una traduzione salvata
        // (difetto W5, 28/09/2026 — vedi App\Support\Translations). Il
        // pannello admin fonde questo array nella create() di una bozza nuova:
        // lì le chiavi a null non cambiano niente, la lingua resta assente.
        if ($this->isEvent) {
            $attributes['meeting_point'] = Translations::replacing($this->meetingPoint);
        } else {
            $attributes['operating_area'] = Translations::replacing($this->operatingArea);
        }

        return $attributes;
    }
}
