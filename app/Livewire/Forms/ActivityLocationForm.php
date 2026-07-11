<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
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

    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:128'],
            'city' => ['required', 'string', 'max:64'],
            'province' => ['required', 'string', 'max:64'],
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
