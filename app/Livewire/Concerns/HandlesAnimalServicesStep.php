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

    /** Dettaglio per "Altro". */
    public string $other = '';

    public function mountHandlesAnimalServicesStep(): void
    {
        $draft = $this->draft();
        $this->services = $draft->animal_services ?? [];
        $this->other = $draft->animal_services_other ?? '';
    }

    public function next(): void
    {
        $this->validate([
            'services' => ['array'],
            'services.*' => ['string'],
            'other' => ['nullable', 'string', 'max:200'],
        ]);

        $this->saveStep(
            ['animal_services' => $this->services, 'animal_services_other' => $this->other],
            $this->animalServicesStep(),
        );
        $this->redirectRoute($this->animalServicesNextRoute());
    }

    /** `current_step` da registrare per lo step nel flusso corrente. */
    abstract protected function animalServicesStep(): int;

    /** Nome della rotta dello step successivo. */
    abstract protected function animalServicesNextRoute(): string;
}
