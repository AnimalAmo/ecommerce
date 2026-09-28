<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use App\Support\Translations;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Attività/Eventi — step 2 "Nome". Oltre al nome, qui stanno le DUE liste di
 * tipologie a scelta multipla (risposte della cliente, 27/09/2026): le
 * categorie professionali per le attività, le tipologie di evento per gli
 * eventi.
 *
 * Stanno in questo step e non in quello del tipo per una ragione già pagata
 * una volta: chi entra dalle card "Servizio professionale" e "Evento" SALTA lo
 * step 1 — il funnel lo manda diretto a questa rotta con `type` già scritto —
 * e ActivityName è l'unico componente che tutte le strade attraversano. Un
 * campo messo nello step del tipo non lo vedrebbe proprio chi ne ha bisogno.
 */
class ActivityName extends Component
{
    use InteractsWithStructureDraft;

    /** Nome dell'attività/evento, localizzato: it obbligatorio, en opzionale. */
    public array $name = ['it' => '', 'en' => ''];

    /**
     * Tipologie selezionate. Una property sola per i due rami: quale colonna
     * leggere e scrivere lo dice `$isEvent`, così il ramo abbandonato non viene
     * nemmeno sfiorato e le categorie professionali sopravvivono a un passaggio
     * da Attività a Evento (sono l'identità del professionista).
     */
    public array $categories = [];

    /** Dettaglio di "Altro", localizzato it/en. */
    public array $categoriesOther = ['it' => '', 'en' => ''];

    /**
     * Flag di controllo, non persistito, stesso idioma di ActivityInfoForm.
     * Non è #[Locked] per restare uguale alla sorella e testabile: manometterlo
     * sposta la scrittura sulla colonna dell'altro ramo, e il publisher scarta
     * già le tipologie che non appartengono al tipo pubblicato.
     */
    public bool $isEvent = false;

    public function mount(): void
    {
        $draft = $this->draft();

        $this->name = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('name'));
        $this->isEvent = $draft->type === 'eventi';
        $this->categories = $draft->{$this->categoriesColumn()} ?? [];
        $this->categoriesOther = array_merge(
            ['it' => '', 'en' => ''],
            $draft->getTranslations($this->categoriesOtherColumn()),
        );
    }

    public function next(): void
    {
        $rules = [
            'name.it' => ['required', 'string', 'max:128'],
            'name.en' => ['nullable', 'string', 'max:128'],
            // Facoltativa, esattamente come `animalServices`: obbligatoria
            // renderebbe non risalvabile ogni bozza già aperta, che ha la
            // colonna NULL e nessun modo di saperlo.
            'categories' => ['array'],
            // La whitelist va sull'ELEMENTO, non sul campo: `Rule::in()`
            // messa su `categories` confronterebbe un array con delle
            // stringhe e rifiuterebbe qualunque selezione.
            'categories.*' => ['string', Rule::in(ServiceOptionLabels::slugs($this->group()))],
        ];

        // Il testo di "Altro" si valida solo se il campo è disegnato: senza la
        // casella il blade non lo mostra, quindi un suo errore non avrebbe dove
        // comparire e bloccherebbe lo step in silenzio. Tanto non si salva (F6).
        if ($this->choosesOther()) {
            $rules['categoriesOther.it'] = ['nullable', 'string', 'max:200'];
            $rules['categoriesOther.en'] = ['nullable', 'string', 'max:200'];
        }

        $this->validate($rules, ['name.it.required' => __('partner.activity_name.error_required')]);

        // Una lingua lasciata vuota va a null e non viene fatta cadere: su EN
        // scatta il fallback IT anche quando c'era già una traduzione salvata
        // (difetto W5, 28/09/2026 — vedi App\Support\Translations).
        //
        // Difetto F6 (28/09/2026): il testo libero si scriveva anche con
        // "Altro" non più selezionato. Il campo sparisce con la casella, quindi
        // il partner non lo vede più, ma restava a bozza, il publisher lo
        // copiava e la scheda stampava «Tipologia: Toelettatore» con sotto la
        // riga grigia di un "Altro" abbandonato. Senza la casella la colonna si
        // svuota. La property invece resta com'è: rispuntando "Altro" prima di
        // salvare, il partner ritrova quello che aveva scritto.
        $this->saveStep([
            'name' => Translations::replacing($this->name),
            $this->categoriesColumn() => $this->categories,
            $this->categoriesOtherColumn() => Translations::replacing($this->choosesOther() ? $this->categoriesOther : []),
        ], 2);
        $this->redirectRoute('partner.activity.location');
    }

    /** "Altro" è fra le tipologie selezionate? È la condizione con cui il blade disegna il suo testo libero. */
    private function choosesOther(): bool
    {
        return in_array('altro', $this->categories, true);
    }

    /** Gruppo di ServiceOptionLabels del ramo corrente. */
    private function group(): string
    {
        return $this->isEvent ? 'event_category' : 'activity_category';
    }

    /** Colonna JSON della bozza del ramo corrente. */
    private function categoriesColumn(): string
    {
        return $this->isEvent ? 'event_categories' : 'activity_categories';
    }

    /** Colonna tradotta del testo libero di "Altro" del ramo corrente. */
    private function categoriesOtherColumn(): string
    {
        return $this->categoriesColumn().'_other';
    }

    /**
     * Difetto W4 dell'audit dei flussi (28/09/2026): «Indietro» era un href
     * fisso a `partner.activity.type`, l'unico back link del wizard che non
     * passava da serviceChoiceBackUrl(). Ma le card «Servizio professionale»
     * ed «Evento» arrivano qui dritte, saltando lo step del tipo: tornando
     * indietro il partner atterrava sulla scelta Attività/Evento che il funnel
     * aveva già fatto, e chi modificava un servizio di «I miei servizi» non
     * tornava alla lista.
     *
     * Ora la destinazione è la stessa dei tre step del tipo: la lista per chi
     * modifica un servizio, altrimenti la scelta delle card, che riprende la
     * bozza in corso (CreateService::mount). Vale anche per chi è passato dallo
     * step del tipo con la card «Attività»: la sua bozza è identica a quella
     * della card «Servizio professionale» (`attivita` di tipo `attivita`, step
     * 1), quindi dai dati non si distingue, e le card sono la sola
     * destinazione che non atterra mai su uno step saltato. Lì la card della
     * bozza è già selezionata (CreateService::cardOf) e «Avanti» riprende il
     * suo percorso: lo step del tipo per «Attività», questo per «Evento».
     */
    public function render()
    {
        return view('livewire.partner.activity.activity-name', [
            'categoryOptions' => ServiceOptionLabels::options($this->group()),
            'backUrl' => $this->serviceChoiceBackUrl(),
        ])->title(__('partner.activity_name.title'));
    }
}
