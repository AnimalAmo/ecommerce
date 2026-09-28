<?php

namespace App\Livewire\Concerns;

use App\Services\Partner\ServiceOptionLabels;
use App\Support\Translations;
use Illuminate\Validation\Rule;

/**
 * Step "servizi per gli animali" condiviso tra i flussi hotel e attività:
 * multi-scelta dei servizi dedicati + campo libero "Altro". Persistenza e
 * navigazione restano nel componente, che deve usare
 * {@see InteractsWithStructureDraft} e definire step finale e rotta successiva.
 */
trait HandlesAnimalServicesStep
{
    /** Servizi dedicati agli animali selezionati (multi-scelta). */
    public array $services = [];

    /** Dettaglio per "Altro", localizzato it/en. */
    public array $other = ['it' => '', 'en' => ''];

    /**
     * I servizi si rileggono filtrati sugli slug del gruppo, per la stessa
     * ragione di HotelServicesForm::setFromDraft(): una bozza con uno slug che
     * il wizard non disegna (DemoUserSeeder semina 'servizio_veterinario')
     * bloccherebbe lo step sull'`in:` di next(), senza una casella da togliere.
     */
    public function mountHandlesAnimalServicesStep(): void
    {
        $draft = $this->draft();
        $this->services = array_values(array_intersect(
            $draft->animal_services ?? [],
            ServiceOptionLabels::slugs('animal_services'),
        ));
        $this->other = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('animal_services_other'));
    }

    public function next(): void
    {
        // Ogni voce contro la mappa da cui la vista la disegna, come fa già il
        // pannello admin: uno slug inventato non arriva alla bozza.
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string', Rule::in(ServiceOptionLabels::slugs('animal_services'))],
            'other.it' => ['nullable', 'string', 'max:200'],
            'other.en' => ['nullable', 'string', 'max:200'],
        ]);

        $this->saveStep(
            ['animal_services' => $this->services, 'animal_services_other' => Translations::replacing($this->other)],
            $this->animalServicesStep(),
        );
        $this->redirectRoute($this->animalServicesNextRoute());
    }

    /** `current_step` da registrare per lo step nel flusso corrente. */
    abstract protected function animalServicesStep(): int;

    /** Nome della rotta dello step successivo. */
    abstract protected function animalServicesNextRoute(): string;
}
