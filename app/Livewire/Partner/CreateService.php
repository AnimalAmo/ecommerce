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
     * Riprende la bozza in corso della sessione, a qualunque step sia arrivata.
     * È la pagina su cui atterra l'«Indietro» del primo step di ogni famiglia
     * (serviceChoiceBackUrl), e il partner deve ritrovarci il suo lavoro.
     *
     * Difetto W2 (audit del 28/09/2026): qui si riprendeva solo una bozza allo
     * step 0. Una più avanzata veniva scollegata dalla sessione e se ne apriva
     * una nuova, mentre la vecchia restava a database con nome, categorie e
     * indirizzo, invisibile a ogni schermata partner. Bastava UN «Indietro»
     * dalle card «Servizio professionale» ed «Evento», che salvano subito lo
     * step 1.
     *
     * Si scollega ancora dalla sessione, aprendo una bozza nuova:
     *  - un servizio completato o in attesa di Stripe (un servizio in modifica
     *    da "I miei servizi"): next() ne riscriverebbe la categoria,
     *    trasformandolo nel servizio successivo. Il suo "Indietro" riporta
     *    alla lista, non qui;
     *  - con `?nuovo=1` (link «Crea servizio» dell'header), una bozza in corso:
     *    il partner ha chiesto un servizio nuovo, e la vecchia non si perde
     *    perché "I miei servizi" la elenca con «Riprendi». Una bozza sotto la
     *    soglia di STARTED_STEP invece si riusa: porta solo card e tipologia,
     *    e scollegarla lascerebbe una riga vuota a ogni clic.
     *
     * draft() subito, non in next(): fissa `draftId` (Locked) sulla bozza,
     * anche se un'altra scheda nel frattempo riscrive la sessione.
     *
     * La card della bozza ripresa vince sulla preselezione dell'iscrizione:
     * è la scelta che il partner ha già fatto in questo funnel.
     */
    public function mount(): void
    {
        $draft = $this->draft();

        if ($draft->status !== StructureDraft::STATUS_DRAFT
            || $draft->isAwaitingPublication()
            || (request()->boolean('nuovo') && $draft->isInProgress())) {
            $draft = $this->detachDraft();
        }

        $this->service = $this->cardOf($draft) ?: $this->registrationChoice();
    }

    /**
     * La card che ha prodotto la bozza, ricostruita dai suoi dati. Le card
     * «Attività» e «Servizio professionale» scrivono la stessa bozza
     * (`attivita` di tipo `attivita`) e non si distinguono: vale «Attività».
     * Una bozza di tipo `eventi` mostra «Evento», la card che la descrive,
     * da qualunque delle due strade sia arrivata.
     */
    private function cardOf(StructureDraft $draft): string
    {
        return match ($draft->service_category) {
            null => '',
            'attivita' => $draft->type === 'eventi' ? 'eventi' : 'attivita',
            'smartbox' => 'smartbox',
            default => 'struttura',
        };
    }

    /**
     * Si continua sulla bozza ripresa? Sì se è appena nata (step 0: porta solo
     * la card cliccata, che si riscrive). Oltre, solo se la card resta nella
     * sua famiglia e non le cambia il tipo: cambiarle famiglia lascerebbe
     * scritte le colonne dell'altro ramo, e le card «Servizio professionale» ed
     * «Evento» fissano il tipo senza passare dallo step del tipo, che è il solo
     * ad azzerare i campi del ramo abbandonato (ActivityType::clearedFields).
     * La card «Attività» passa da lì, quindi può riprendere anche un evento.
     */
    private function fitsDraft(StructureDraft $draft, string $category, ?string $presetType): bool
    {
        if ($draft->current_step === 0) {
            return true;
        }

        if (StructureDraft::familyOf($category) !== $draft->family()) {
            return false;
        }

        return $presetType === null || $draft->type === null || $draft->type === $presetType;
    }

    /** Scollega la bozza dalla sessione e ne apre una nuova. */
    private function detachDraft(): StructureDraft
    {
        session()->forget('structure_draft_id');
        $this->draftId = null;

        return $this->draft();
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
     * La regola regge anche ora che le bozze in corso si riprendono (W2): la
     * preselezione serve solo quando mount() ha una bozza senza card, cioè
     * senza nulla da riprendere. Se il partner ha già una bozza con la card,
     * in sessione vince quella (cardOf); fuori sessione — sessione scaduta,
     * «Crea servizio» con `?nuovo=1` — sta in "I miei servizi", e la pagina
     * sta aprendo un servizio NUOVO: riproporgli la card dell'iscrizione
     * sarebbe di nuovo la scelta di ieri al posto di quella del funnel.
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
        $category = $presetType === null ? $this->service : 'attivita';

        // Card diversa da quella della bozza ripresa: bozza nuova, non la
        // stessa riga con un'altra famiglia (vedi fitsDraft()). La vecchia non
        // si perde: se è in corso (ha passato il nome), "I miei servizi" la
        // elenca con «Riprendi»; se porta solo card e tipologia, non c'era
        // niente da perdere.
        if (! $this->fitsDraft($this->draft(), $category, $presetType)) {
            $this->detachDraft();
        }

        // Step 1 e non 0 per le due card che saltano lo step del tipo: è lo
        // step che quel percorso ha davvero superato, e resumeRoute() riparte
        // così dal nome, non dalla scelta Attività/Evento.
        $this->saveStep([
            'service_category' => $category,
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
