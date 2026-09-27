<?php

namespace App\Livewire\Partner;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Models\Structure\StructureDraft;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateService extends Component
{
    use InteractsWithStructureDraft;

    /**
     * Tipologie offerte da questa pagina, nell'ordine delle card (le etichette
     * stanno nel blade). "Evento" è la quarta voce chiesta dalla cliente il
     * 27/09/2026: il valore è `eventi`, lo stesso slug che la bozza porta in
     * `type`, non `evento`.
     */
    private const SERVICES = ['struttura', 'attivita', 'servizi', 'eventi', 'smartbox'];

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
     *
     * La bozza ripresa vince sempre sulla preselezione dell'iscrizione: allo
     * step 0 la sua `service_category` è esattamente la card cliccata
     * (struttura, attivita o smartbox — "Servizi" ed "Evento" salvano step 1 e
     * quindi non si riprendono), quindi il partner ritrova la propria scelta.
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

        $this->service = $draft->service_category ?? $this->registrationChoice();
    }

    /**
     * Preselezione al PRIMO ingresso nel funnel: la tipologia scelta nello step
     * 2 dell'iscrizione (richiesta della cliente, 27/09/2026 — la scelta fatta
     * iscrivendosi deve arrivare fino alla creazione della prima scheda, «così
     * il percorso è corretto fin dall'inizio»). Resta una preselezione: la
     * schermata non sparisce e la card si cambia.
     *
     * Si legge una volta sola e la colonna non si riscrive da qui. Il "una
     * volta" non è un flag da consumare ma un fatto: si preseleziona solo se
     * nessuna bozza di questo partner ha ancora una `service_category`. Appena
     * passa da next() la bozza ce l'ha, e dal giro dopo non si preseleziona più
     * niente. Così chi si è iscritto come Struttura e qui sceglie Evento non si
     * ritrova Struttura ripreselezionata tornando indietro: vince la scelta del
     * funnel, e nel profilo resta scritto ciò che ha scelto in iscrizione.
     *
     * La bozza creata da mount() qui sopra ha `service_category` nulla, quindi
     * non si conta da sé. Il valore si filtra sulle card di questa pagina: una
     * colonna vuota (partner storici, partner creati dall'admin) o un valore che
     * non è più una card non devono selezionare nulla, altrimenti il radio
     * resterebbe muto e "Avanti" fallirebbe la validazione senza che il partner
     * abbia toccato niente.
     */
    private function registrationChoice(): string
    {
        $choice = (string) (Auth::user()?->partnerProfile?->registration_service ?? '');

        if (! in_array($choice, self::SERVICES, true)) {
            return '';
        }

        $alreadyChosen = StructureDraft::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('service_category')
            ->exists();

        return $alreadyChosen ? '' : $choice;
    }

    public function next(): void
    {
        $this->validate(
            ['service' => ['required', 'string', 'in:'.implode(',', self::SERVICES)]],
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
        //
        // "Evento" (quarta card, richiesta della cliente del 27/09/2026) fa la
        // stessa strada con `type` = 'eventi': anche lì la scelta
        // Attività/Evento l'ha già fatta la card. Scrivere subito il `type` non
        // è un lusso — lo step delle informazioni generali ricava `isEvent` da
        // `$draft->type === 'eventi'` (ActivityInfoForm) e senza quel valore
        // chiederebbe i campi dell'attività, senza orari.
        $presetType = match ($this->service) {
            'servizi' => 'attivita',
            'eventi' => 'eventi',
            default => null,
        };

        // Step 1 e non 0 per le due card che saltano lo step del tipo: allo
        // step 0 la bozza resta "appena iniziata" e mount() la butterebbe al
        // primo refresh, perdendo la scelta.
        $this->saveStep([
            'service_category' => $presetType === null ? $this->service : 'attivita',
            ...($presetType === null ? [] : ['type' => $presetType]),
        ], $presetType === null ? 0 : 1);

        $route = match (true) {
            $presetType !== null => 'partner.activity.name',
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
