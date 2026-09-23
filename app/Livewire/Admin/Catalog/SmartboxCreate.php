<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\CreatesPartnerService;
use App\Livewire\Concerns\ProvidesTimeSlots;
use App\Livewire\Forms\SmartboxMealsForm;
use App\Models\Structure\Structure;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Form;

/**
 * Pannello — crea una smartbox per conto di un partner (spec §5.2): i 12 step
 * del wizard in una pagina sola, con le stesse regole e le stesse chiavi di
 * messaggio, così il partner può poi riaprirla dal suo percorso.
 *
 * Due differenze volute rispetto al wizard:
 * - le strutture incluse sono quelle del partner SCELTO, non di chi è loggato
 *   (nel wizard la lista nasce da Auth::id());
 * - `animal_services_other` si salva come ['it' => ...]. Lo step del partner
 *   scrive una stringa nuda, che spatie mette sotto il locale della richiesta:
 *   qui il locale è sempre 'it' ma scriverlo esplicito toglie la dipendenza.
 *
 * I gruppi di opzioni li legge dal Task 1: `diets` e `smartbox_amenities` sono
 * i nomi veri. Non se ne aggiungono di nuovi qui.
 */
class SmartboxCreate extends Component
{
    use CreatesPartnerService, ProvidesTimeSlots;

    /** soggiorno | benessere | avventura (decide il ProductType a catalogo). */
    public string $type = 'soggiorno';

    /** @var array<string, string> */
    public array $name = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $detailedDescription = ['it' => '', 'en' => ''];

    /** Durata del soggiorno in giorni (non la validità del cofanetto: quella è 12 mesi fissi). */
    public ?int $durationDays = null;

    public SmartboxMealsForm $meals;

    /** Istantanea dei pasti per l'esclusività di "Nessuno". */
    public array $mealsPrev = [];

    /** Cosa offre la smartbox (colonna `services`, gruppo `smartbox_amenities`). @var list<string> */
    public array $offers = [];

    /** Servizi aggiuntivi (colonna `additional_services`, gruppo `smartbox_additional`). @var list<string> */
    public array $additional = [];

    /** Cosa è incluso (colonna `included_services`, gruppo `services`). @var list<string> */
    public array $included = [];

    /** @var list<string> */
    public array $animalServices = [];

    public string $animalOther = '';

    /** Id (stringa) delle strutture del partner incluse nel cofanetto. @var list<string> */
    public array $structures = [];

    /** Giorni di cancellazione gratuita: 30 | 15 | 7 | 1. */
    public string $when = '1';

    /** Prezzo in euro, stringa: il publisher lo converte in cents. */
    public string $price = '';

    /**
     * Cache di richiesta: regole, view e reset chiedono la stessa lista.
     *
     * @var array<string, string>|null
     */
    private ?array $structureOptionsCache = null;

    public function mount(): void
    {
        $this->mealsPrev = $this->meals->meals;
    }

    /**
     * "Nessuno" esclude i pasti e viceversa. Nel wizard il gancio si chiama
     * updatedFormMeals perché lì la property è $form: qui la property è $meals,
     * quindi il nome del gancio cambia di conseguenza.
     */
    public function updatedMealsMeals(): void
    {
        $added = array_values(array_diff($this->meals->meals, $this->mealsPrev));

        if (in_array('nessuno', $added, true)) {
            $this->meals->meals = ['nessuno'];
        } elseif ($added !== []) {
            $this->meals->meals = array_values(array_diff($this->meals->meals, ['nessuno']));
        }

        $this->mealsPrev = $this->meals->meals;
    }

    /**
     * Stesso ordine delle altre due famiglie: chiavi proprie, merge delle
     * regole del Form con `=`, rafforzamenti accodati in fondo. La prima
     * stesura non aveva nessun merge e dichiarava `meals.meals.*` e
     * `meals.dietary.*` a mano: le regole di SmartboxMealsForm (`meals` array,
     * `mealTimes.*.from|to`, `dietary` array) non venivano applicate affatto, e
     * una regola aggiunta domani a quel Form non arriverebbe mai al pannello.
     */
    /**
     * Senza "altro" la textarea non si vede più (la nasconde la vista), ma la
     * proprietà resta compilata e finirebbe sulla bozza: un residuo che
     * l'admin non può più leggere né cancellare. Stessa forma di
     * `StructureCreate::updatedSmartboxConsent()`.
     */
    public function updatedAnimalServices(): void
    {
        if (! in_array('altro', $this->animalServices, true)) {
            $this->animalOther = '';
        }
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::in(ServiceOptionLabels::slugs('smartbox_type'))],
            // max:110 solo sul nome: è il JSON it+en dentro un varchar(255).
            'name.it' => ['required', 'string', 'max:110'],
            'name.en' => ['nullable', 'string', 'max:110'],
            // Descrizioni ai 200 del wizard (SmartboxDescription): stringerle
            // bloccherebbe il partner su testi che ha già salvato.
            'description.it' => ['required', 'string', 'max:200'],
            'description.en' => ['nullable', 'string', 'max:200'],
            'detailedDescription.it' => ['required', 'string', 'max:200'],
            'detailedDescription.en' => ['nullable', 'string', 'max:200'],
            'durationDays' => ['required', 'integer', 'min:1', 'max:365'],
            'offers' => ['array'],
            'offers.*' => [Rule::in(ServiceOptionLabels::slugs('smartbox_amenities'))],
            'additional' => ['array'],
            'additional.*' => [Rule::in(ServiceOptionLabels::slugs('smartbox_additional'))],
            'included' => ['array'],
            'included.*' => [Rule::in(ServiceOptionLabels::slugs('services'))],
            'animalServices' => ['array'],
            'animalServices.*' => [Rule::in(ServiceOptionLabels::slugs('animal_services'))],
            'animalOther' => ['nullable', 'string', 'max:200'],
            'structures' => ['array'],
            // Le chiavi ammesse si ricalcolano qui: il client può scrivere
            // qualsiasi id, ma passano solo le strutture del partner scelto.
            'structures.*' => [Rule::in(array_keys($this->structureOptions()))],
            'when' => ['required', Rule::in(ServiceOptionLabels::slugs('cancellation'))],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'photos' => ['array'],
            'photos.*' => ['image', 'max:8192'],
        ];

        // 1. Regole del Form object (stesso ciclo, identico, delle altre due
        //    famiglie: si copia-incolla).
        foreach ($this->sectionForms() as $property => $form) {
            foreach ($form->rules() as $key => $rule) {
                $rules[$property.'.'.$key] = $rule;
            }
        }

        // 2. E SOLO ADESSO i rafforzamenti, ACCODATI alle regole appena fuse.
        $rules['meals.meals.*'][] = Rule::in(ServiceOptionLabels::slugs('meals'));
        $rules['meals.dietary.*'][] = Rule::in(ServiceOptionLabels::slugs('diets'));

        return $rules;
    }

    /**
     * L'unico Form object della pagina. Esiste per avere in tutte e tre le
     * famiglie lo stesso ciclo di merge, parola per parola.
     *
     * @return array<string, Form>
     */
    private function sectionForms(): array
    {
        return ['meals' => $this->meals];
    }

    public function messages(): array
    {
        $messages = [
            'type.required' => __('partner.smartbox_type.error_required'),
            'type.in' => __('partner.smartbox_type.error_required'),
            'name.it.required' => __('partner.smartbox_name.error_required'),
            'name.it.max' => __('admin-catalog.create.validation.name_max'),
            'name.en.max' => __('admin-catalog.create.validation.name_max'),
            'description.it.required' => __('partner.smartbox_description.error_required'),
            'description.it.max' => __('admin-catalog.create.validation.text_max'),
            'description.en.max' => __('admin-catalog.create.validation.text_max'),
            'detailedDescription.it.required' => __('partner.smartbox_description.error_required'),
            'detailedDescription.it.max' => __('admin-catalog.create.validation.text_max'),
            'detailedDescription.en.max' => __('admin-catalog.create.validation.text_max'),
            'durationDays.required' => __('partner.smartbox_duration.error_required'),
            'durationDays.integer' => __('partner.smartbox_duration.error_required'),
            'durationDays.min' => __('partner.smartbox_duration.error_min'),
            'durationDays.max' => __('partner.smartbox_duration.error_min'),
            'when.required' => __('partner.hotel_cancellation.error_required'),
            'when.in' => __('partner.hotel_cancellation.error_required'),
            'price.required' => __('partner.smartbox_price.error_required'),
            'offers.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'additional.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'included.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'animalServices.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'meals.meals.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'meals.dietary.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'structures.*.in' => __('admin-catalog.create.smartbox.structure_not_his'),
            'photos.*.image' => __('admin-catalog.create.validation.photo_image'),
            'photos.*.max' => __('admin-catalog.create.validation.photo_max'),
        ];

        foreach ($this->sectionForms() as $property => $form) {
            // `Livewire\Form` non dichiara `messages()` nella classe base: il
            // `getMessages()` di HandlesValidation lo cerca con method_exists.
            // Oggi nessun Form di questa pagina ce l'ha, e senza il controllo
            // la riga sotto sarebbe un «Call to undefined method». Il ciclo
            // c'è perché un messaggio aggiunto domani a un Form deve arrivare
            // al pannello, non sparire in silenzio (come in StructureCreate).
            if (! method_exists($form, 'messages')) {
                continue;
            }

            foreach ($form->messages() as $key => $message) {
                $messages[$property.'.'.$key] = $message;
            }
        }

        return $messages;
    }

    /**
     * Nomi dei campi in italiano. Senza questo, le regole di SmartboxMealsForm
     * che non hanno un messaggio proprio uscirebbero come «Il campo meals.meal
     * times.0.from …»: un percorso di proprietà a video in un pannello che è
     * solo in italiano.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $prefix = 'admin-catalog.create.smartbox.';

        return [
            'type' => __($prefix.'section_type'),
            'name.it' => __($prefix.'field_name'),
            'name.en' => __($prefix.'field_name'),
            'description.it' => __($prefix.'field_description'),
            'description.en' => __($prefix.'field_description'),
            'detailedDescription.it' => __($prefix.'field_detailed_description'),
            'detailedDescription.en' => __($prefix.'field_detailed_description'),
            'durationDays' => __($prefix.'field_duration'),
            'meals.meals' => __($prefix.'field_meals'),
            'meals.dietary' => __('partner.smartbox_meals.dietary_heading'),
            'offers' => __($prefix.'field_offers'),
            'additional' => __('partner.smartbox_offers.additional_heading'),
            'included' => __($prefix.'field_included'),
            'animalServices' => __($prefix.'field_animal_services'),
            'animalOther' => __($prefix.'field_animal_other'),
            'structures' => __($prefix.'field_structures'),
            'when' => __($prefix.'field_cancellation'),
            'price' => __($prefix.'field_price'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.catalog.smartbox-create', [
            ...$this->partnerViewData(),
            'times' => $this->times(),
            // Stessa fonte delle altre due famiglie: slug => etichetta.
            'cancellationOptions' => ServiceOptionLabels::options('cancellation'),
            'structureOptions' => $this->structureOptions(),
            'mealOptions' => ServiceOptionLabels::options('meals'),
            'dietaryOptions' => ServiceOptionLabels::options('diets'),
            'offerOptions' => ServiceOptionLabels::options('smartbox_amenities'),
            'additionalOptions' => ServiceOptionLabels::options('smartbox_additional'),
            'includedOptions' => ServiceOptionLabels::options('services'),
            'animalOptions' => ServiceOptionLabels::options('animal_services'),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-catalog.create.smartbox.title'));
    }

    protected function serviceCategory(): string
    {
        return 'smartbox';
    }

    protected function photoDirectory(): string
    {
        return 'smartbox-photos';
    }

    /**
     * Cambiando partner cambia la lista: la selezione vecchia non vale più.
     * Il nome è quello del trait — `resetPartnerSelections()` sarebbe codice
     * morto e le strutture incluse non si azzererebbero.
     */
    protected function resetPartnerDependentState(): void
    {
        $this->structures = [];
        $this->structureOptionsCache = null;
    }

    /**
     * Valida e compone. Unico punto di validazione della pagina, chiamato dal
     * trait PRIMA di collectPhotos(): un errore non lascia foto su disco.
     * La virgola italiana si normalizza qui ('215,50' non passa `numeric`).
     */
    protected function draftAttributes(): array
    {
        $this->price = str_replace(',', '.', trim($this->price));

        // Tre argomenti, come nelle altre due famiglie.
        $this->validate($this->rules(), $this->messages(), $this->validationAttributes());

        $filled = fn (array $translations): array => array_filter($translations, fn ($value) => filled($value));

        return [
            'type' => $this->type,
            'name' => $filled($this->name),
            'description' => $filled($this->description),
            'detailed_description' => $filled($this->detailedDescription),
            'duration_days' => $this->durationDays,
            ...$this->meals->toDraft(),
            'services' => $this->offers,
            'additional_services' => $this->additional,
            'included_services' => $this->included,
            'animal_services' => $this->animalServices,
            // Esplicitamente in italiano: lo step partner scrive una stringa
            // nuda, che finisce sotto il locale della richiesta.
            'animal_services_other' => $filled(['it' => $this->animalOther]),
            'smartbox_structures' => $this->structures,
            'cancellation_when' => $this->when,
            'price' => $this->price,
        ];
    }

    /**
     * Strutture a catalogo del partner scelto, chiave = id come stringa (è così
     * che il wizard le salva). `whereNotNull` non è ridondante: le righe mock
     * non hanno proprietario e `where('user_id', null)` diventerebbe `IS NULL`,
     * offrendole per qualunque partner.
     *
     * @return array<string, string>
     */
    private function structureOptions(): array
    {
        $partner = $this->partner();

        if ($partner === null) {
            return [];
        }

        return $this->structureOptionsCache ??= Structure::withHidden()
            ->whereNotNull('user_id')
            ->where('user_id', $partner->id)
            ->orderBy('position')
            ->get(['id', 'name', 'location'])
            ->mapWithKeys(fn (Structure $structure): array => [
                (string) $structure->id => trim($structure->name.' — '.$structure->location),
            ])
            ->all();
    }
}
