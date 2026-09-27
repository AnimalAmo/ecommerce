<?php

namespace App\Livewire\Partner\Activity;

use App\Livewire\Concerns\InteractsWithStructureDraft;
use App\Services\Partner\ServiceOptionLabels;
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
        $this->validate(
            [
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
                'categoriesOther.it' => ['nullable', 'string', 'max:200'],
                'categoriesOther.en' => ['nullable', 'string', 'max:200'],
            ],
            ['name.it.required' => __('partner.activity_name.error_required')],
        );

        // Le traduzioni vuote non vengono salvate: su EN scatta il fallback IT.
        $this->saveStep([
            'name' => array_filter($this->name, fn ($value) => filled($value)),
            $this->categoriesColumn() => $this->categories,
            $this->categoriesOtherColumn() => array_filter($this->categoriesOther, fn ($value) => filled($value)),
        ], 2);
        $this->redirectRoute('partner.activity.location');
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

    public function render()
    {
        return view('livewire.partner.activity.activity-name', [
            'categoryOptions' => ServiceOptionLabels::options($this->group()),
        ])->title(__('partner.activity_name.title'));
    }
}
