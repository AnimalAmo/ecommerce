<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Models\Structure\StructureDraft;
use Livewire\Component;

class CreateService extends Component
{
    use InteractsWithStructureDraft;

    /** Tipo di servizio scelto (radio, scelta singola). */
    public string $service = '';

    /**
     * Riprende solo una bozza appena iniziata: di chi è loggato (draft()),
     * `draft`, non in attesa e ferma allo step 0, cioè toccata solo da questa
     * pagina. Così un refresh o l'"Indietro" dallo step del tipo non creano
     * una riga a ogni visita. Qualunque altra bozza in sessione (in attesa di
     * Stripe, un servizio in modifica, un wizard già avanzato) si lascia: prima
     * next() ne riscriveva la categoria, trasformandola nel servizio successivo.
     * La modifica di un servizio esistente passa da "I miei servizi", e il suo
     * "Indietro" riporta lì (serviceChoiceBackUrl), non qui.
     * draft() subito, non in next(): fissa `draftId` (Locked) sulla bozza,
     * anche se un'altra scheda nel frattempo riscrive la sessione.
     */
    public function mount(): void
    {
        $draft = $this->draft();

        if ($draft->status !== StructureDraft::STATUS_DRAFT
            || $draft->isAwaitingPublication()
            || $draft->current_step > 0) {
            session()->forget('structure_draft_id');
            $this->draftId = null;
            $draft = $this->draft();
        }

        $this->service = $draft->service_category ?? '';
    }

    public function next(): void
    {
        $this->validate(
            ['service' => ['required', 'string', 'in:struttura,attivita,servizi,smartbox']],
            ['service.required' => __('partner.create_service.error_required'), 'service.in' => __('partner.create_service.error_required')],
        );

        // "Servizi" portava agli step della struttura ricettiva, quindi un
        // toelettatore o un dog sitter si vedeva chiedere hotel, B&B,
        // agriturismo o casa vacanza. La cliente ha chiesto il contrario
        // (29/09/2026): i professionisti stanno in "Attività". La bozza nasce
        // quindi come 'attivita' di tipo 'attivita' e salta la scelta
        // Attività/Evento, che chi ha cliccato "Servizi" ha già fatto.
        //
        // service_category è 'attivita' e non 'servizi' di proposito: family()
        // manda 'servizi' su StructurePublisher, e una bozza compilata col
        // wizard attività pubblicata come Struttura sarebbe rotta. Le bozze
        // storiche con 'servizi' restano dove sono: sono state compilate col
        // percorso hotel e sono a catalogo come strutture.
        $isProfessional = $this->service === 'servizi';

        $this->saveStep([
            'service_category' => $isProfessional ? 'attivita' : $this->service,
            ...($isProfessional ? ['type' => 'attivita'] : []),
        ], $isProfessional ? 1 : 0);

        $route = match (true) {
            $isProfessional => 'partner.activity.name',
            $this->service === 'attivita' => 'partner.activity.type',
            $this->service === 'smartbox' => 'partner.smartbox.type',
            default => 'partner.structure.type',
        };

        $this->redirectRoute($route);
    }

    public function render()
    {
        return view('livewire.partner.create-service')
            ->title(__('partner.create_service.title'));
    }
}
