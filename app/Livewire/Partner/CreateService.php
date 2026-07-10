<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use Livewire\Component;

class CreateService extends Component
{
    use InteractsWithStructureDraft;

    /** Tipo di servizio scelto (radio, scelta singola). */
    public string $service = '';

    public function mount(): void
    {
        $this->service = $this->draft()->service_category ?? '';
    }

    public function next(): void
    {
        $this->validate(
            ['service' => ['required', 'string', 'in:struttura,attivita,servizi,smartbox']],
            ['service.required' => __('partner.create_service.error_required'), 'service.in' => __('partner.create_service.error_required')],
        );

        $this->saveStep(['service_category' => $this->service], 0);

        // Il flusso "servizi" ha un percorso dedicato non ancora costruito (null → nessun redirect).
        $route = match ($this->service) {
            'struttura' => 'partner.structure.type',
            'attivita' => 'partner.activity.type',
            'smartbox' => 'partner.smartbox.type',
            default => null,
        };

        if ($route !== null) {
            $this->redirectRoute($route);
        }
    }

    public function render()
    {
        return view('livewire.partner.create-service')
            ->title(__('partner.create_service.title'));
    }
}
