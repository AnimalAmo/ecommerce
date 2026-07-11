<?php

namespace App\Livewire\Concerns;

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

    public function mountHandlesAnimalServicesStep(): void
    {
        $draft = $this->draft();
        $this->services = $draft->animal_services ?? [];
        $this->other = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('animal_services_other'));
    }

    public function next(): void
    {
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string'],
            'other.it' => ['nullable', 'string', 'max:200'],
            'other.en' => ['nullable', 'string', 'max:200'],
        ]);

        $this->saveStep(
            ['animal_services' => $this->services, 'animal_services_other' => array_filter($this->other, fn ($value) => filled($value))],
            $this->animalServicesStep(),
        );
        $this->redirectRoute($this->animalServicesNextRoute());
    }

    /** `current_step` da registrare per lo step nel flusso corrente. */
    abstract protected function animalServicesStep(): int;

    /** Nome della rotta dello step successivo. */
    abstract protected function animalServicesNextRoute(): string;
}
