<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Admin\Catalog\Concerns\CreatesPartnerService;
use App\Livewire\Concerns\ProvidesTimeSlots;
use App\Livewire\Forms\ActivityIncludedForm;
use App\Livewire\Forms\ActivityInfoForm;
use App\Livewire\Forms\ActivityLocationForm;
use App\Models\Region\Province;
use App\Services\Partner\ServiceOptionLabels;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Form;

/**
 * Pannello — crea in un colpo solo un'attività o un evento per conto di un
 * partner (spec §5.2). È la pagina unica che comprime i 10 step del wizard
 * partner: stessi campi, stesse regole e stesse chiavi di messaggio, così il
 * partner può poi riaprire la scheda dal suo percorso senza trovarsi bloccato
 * da un valore che il suo wizard rifiuterebbe.
 *
 * Il partner NON è un parametro di mount: arriva dalla query string
 * (`?partner=`), che è dove `CreatesPartnerService::$partnerId` lo legge.
 */
class ActivityCreate extends Component
{
    use CreatesPartnerService, ProvidesTimeSlots;

    /** attivita | eventi: decide orari, descrizione dettagliata e ProductType. */
    public string $type = 'attivita';

    /** @var array<string, string> */
    public array $name = ['it' => '', 'en' => ''];

    public ActivityLocationForm $location;

    /** @var array<string, string> */
    public array $description = ['it' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $detailedDescription = ['it' => '', 'en' => ''];

    public ActivityInfoForm $info;

    public ActivityIncludedForm $included;

    /** @var list<string> */
    public array $animalServices = [];

    /** @var array<string, string> */
    public array $animalOther = ['it' => '', 'en' => ''];

    /** pagamento | gratuito. */
    public string $costType = '';

    public string $pricePerPerson = '';

    /** Giorni di cancellazione gratuita: 30 | 15 | 7 | 1. */
    public string $when = '1';

    public function mount(): void
    {
        // Il form nasce su "Attività": senza questo il Form object resterebbe
        // con isEvent=false per caso e non per scelta (setFromDraft non gira mai).
        $this->info->isEvent = false;
    }

    /**
     * Cambiando tipologia gli orari cambiano statuto. Tornando su "Attività"
     * non basta smettere di salvarli (toDraft già li esclude): restano a video
     * e tornerebbero validi al primo ripensamento. Si azzerano qui, che è anche
     * la correzione del difetto noto del wizard (orari di un evento superstiti
     * in starts_at/ends_at di un'attività).
     */
    public function updatedType(): void
    {
        $this->info->isEvent = $this->type === 'eventi';

        if (! $this->info->isEvent) {
            $this->info->timeStart = '';
            $this->info->timeEnd = '';
        }
    }

    /**
     * Regole del wizard più i tre rafforzamenti del contratto.
     *
     * `max:110` SOLO su nome e punto d'incontro, che sono i campi il cui JSON
     * it+en finisce in un `varchar(255)`. Descrizioni e "altro" restano ai 200
     * del wizard: un limite più stretto bloccherebbe il partner su un testo che
     * ha già salvato dal suo percorso e che non potrebbe più risalvare.
     *
     * Le whitelist usano `slugs()`, mai `options()`.
     *
     * L'ORDINE È PORTANTE ed è lo stesso di StructureCreate e SmartboxCreate:
     * chiavi proprie, poi merge dei Form con `=`, poi rafforzamenti accodati.
     * Con `??=` (com'era scritto) il rafforzamento avrebbe SOSTITUITO la regola
     * del Form: `location.meetingPoint.it` sarebbe diventato `['max:110']` e il
     * punto d'incontro avrebbe smesso di essere obbligatorio; `location.province`
     * sarebbe diventato il solo `exists`, e una provincia vuota avrebbe detto
     * «questa sigla non esiste» invece di «è obbligatoria». Un'attività creata
     * così dal pannello non sarebbe più risalvabile dal partner col suo wizard.
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::in(ServiceOptionLabels::slugs('activity_type'))],
            'name.it' => ['required', 'string', 'max:110'],
            'name.en' => ['nullable', 'string', 'max:110'],
            'description.it' => ['required', 'string', 'max:200'],
            'description.en' => ['nullable', 'string', 'max:200'],
            'animalServices' => ['array'],
            'animalServices.*' => [Rule::in(ServiceOptionLabels::slugs('animal_services'))],
            'animalOther.it' => ['nullable', 'string', 'max:200'],
            'animalOther.en' => ['nullable', 'string', 'max:200'],
            'costType' => ['required', Rule::in(ServiceOptionLabels::slugs('price_type'))],
            'when' => ['required', Rule::in(ServiceOptionLabels::slugs('cancellation'))],
            // Le foto si dichiarano QUI, come nelle altre due famiglie: senza,
            // i due messaggi `photos.*.image` e `photos.*.max` di messages()
            // sarebbero morti e l'unica validazione delle immagini resterebbe
            // quella interna di HandlesPhotoUploads::collectPhotos(), che non
            // ha messaggi propri — un file da 9 MB uscirebbe col testo generico.
            'photos' => ['array'],
            'photos.*' => ['image', 'max:8192'],
        ];

        // Come ActivityDescription: la descrizione dettagliata esiste solo per
        // le attività (sugli eventi non viene nemmeno pubblicata).
        $rules['detailedDescription.it'] = [$this->type === 'attivita' ? 'required' : 'nullable', 'string', 'max:200'];
        $rules['detailedDescription.en'] = ['nullable', 'string', 'max:200'];

        if ($this->costType === 'pagamento') {
            // decimal:0,2 + max: il publisher converte in cents (unsignedInteger).
            $rules['pricePerPerson'] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'];
        }

        // 1. Regole dei Form object (stesso ciclo, identico, delle altre due
        //    famiglie: si copia-incolla).
        foreach ($this->sectionForms() as $property => $form) {
            foreach ($form->rules() as $key => $rule) {
                $rules[$property.'.'.$key] = $rule;
            }
        }

        // 2. E SOLO ADESSO i rafforzamenti, ACCODATI: la chiave è la stessa che
        //    usa il Form, quindi l'errore compare sotto lo stesso campo, ma la
        //    regola del Form resta (province obbligatoria, punto d'incontro
        //    obbligatorio in italiano).
        $rules['location.province'][] = Rule::exists('provinces', 'short_name');
        $rules['location.meetingPoint.it'][] = 'max:110';
        $rules['location.meetingPoint.en'][] = 'max:110';
        $rules['included.services.*'][] = Rule::in(ServiceOptionLabels::slugs('services'));
        $rules['included.additional.*'][] = Rule::in(ServiceOptionLabels::slugs('additional'));
        $rules['included.structureRules.*'][] = Rule::in(ServiceOptionLabels::slugs('rules'));

        // 3. Una riscrittura, non un rafforzamento: `after_or_equal:dateStart`
        //    di ActivityInfoForm nomina una chiave di PRIMO livello. Dentro il
        //    Form funziona (là i campi non hanno prefisso); qui i dati sono
        //    quelli del componente e `dateStart` non esiste, quindi il
        //    confronto non confronta niente — nessun errore, solo una regola
        //    che non c'è. Col percorso completo torna a valere.
        $rules['info.dateEnd'] = ['required', 'date', 'after_or_equal:info.dateStart'];

        return $rules;
    }

    /**
     * Form object le cui regole entrano nel validate unico. Esiste per avere
     * in tutte e tre le famiglie lo stesso ciclo di merge, parola per parola.
     *
     * @return array<string, Form>
     */
    private function sectionForms(): array
    {
        return [
            'location' => $this->location,
            'info' => $this->info,
            'included' => $this->included,
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('partner.activity_type.error_required'),
            'type.in' => __('partner.activity_type.error_required'),
            'name.it.required' => __('partner.activity_name.error_required'),
            'name.it.max' => __('admin-catalog.create.validation.name_max'),
            'name.en.max' => __('admin-catalog.create.validation.name_max'),
            'location.meetingPoint.it.max' => __('admin-catalog.create.validation.name_max'),
            'location.meetingPoint.en.max' => __('admin-catalog.create.validation.name_max'),
            'description.it.required' => __('partner.activity_description.error_required'),
            'description.it.max' => __('admin-catalog.create.validation.text_max'),
            'description.en.max' => __('admin-catalog.create.validation.text_max'),
            'detailedDescription.it.required' => __('partner.activity_description.error_required'),
            'detailedDescription.it.max' => __('admin-catalog.create.validation.text_max'),
            'detailedDescription.en.max' => __('admin-catalog.create.validation.text_max'),
            'costType.required' => __('partner.activity_cost.error_required'),
            'costType.in' => __('partner.activity_cost.error_required'),
            'when.required' => __('partner.hotel_cancellation.error_required'),
            'when.in' => __('partner.hotel_cancellation.error_required'),
            'location.province.exists' => __('admin-catalog.create.validation.province_exists'),
            'animalServices.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'included.services.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'included.additional.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'included.structureRules.*.in' => __('admin-catalog.create.validation.option_unknown'),
            'photos.*.image' => __('admin-catalog.create.validation.photo_image'),
            'photos.*.max' => __('admin-catalog.create.validation.photo_max'),
        ];
    }

    /**
     * Nomi dei campi in italiano. Senza questo, ogni regola dei Form object
     * che non ha un messaggio proprio — `location.address.required`,
     * `location.city.required`, `location.zip.digits`, `info.dateStart.required`,
     * `info.dateEnd.after_or_equal` — uscirebbe come «Il campo location.address
     * è obbligatorio»: un percorso di proprietà a video, in un pannello che è
     * solo in italiano. Le chiavi sono le stesse che disegnano le etichette.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $prefix = 'admin-catalog.create.activity.';

        return [
            'type' => __($prefix.'section_type'),
            'name.it' => __($prefix.'field_name'),
            'name.en' => __($prefix.'field_name'),
            'description.it' => __($prefix.'field_description'),
            'description.en' => __($prefix.'field_description'),
            'detailedDescription.it' => __($prefix.'field_detailed_description'),
            'detailedDescription.en' => __($prefix.'field_detailed_description'),
            'location.address' => __($prefix.'field_address'),
            'location.city' => __($prefix.'field_city'),
            'location.zip' => __($prefix.'field_zip'),
            'location.province' => __($prefix.'field_province'),
            'location.meetingPoint.it' => __($prefix.'field_meeting_point'),
            'location.meetingPoint.en' => __($prefix.'field_meeting_point'),
            'info.dateStart' => __($prefix.'field_date_start'),
            'info.dateEnd' => __($prefix.'field_date_end'),
            'info.timeStart' => __($prefix.'field_time_start'),
            'info.timeEnd' => __($prefix.'field_time_end'),
            'included.additionalOther.it' => __($prefix.'field_additional_other'),
            'included.additionalOther.en' => __($prefix.'field_additional_other'),
            'animalOther.it' => __($prefix.'field_animal_other'),
            'animalOther.en' => __($prefix.'field_animal_other'),
            'costType' => __($prefix.'field_cost_type'),
            'pricePerPerson' => __($prefix.'field_price_per_person'),
            'when' => __($prefix.'field_cancellation'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.catalog.activity-create', [
            ...$this->partnerViewData(),
            'provinces' => Province::orderBy('name')->get(),
            'times' => $this->times(),
            // Stessa fonte delle altre due famiglie: slug => etichetta, la
            // vista non ricompone niente a mano.
            'cancellationOptions' => ServiceOptionLabels::options('cancellation'),
            'serviceOptions' => ServiceOptionLabels::options('services'),
            'additionalOptions' => ServiceOptionLabels::options('additional'),
            'ruleOptions' => ServiceOptionLabels::options('rules'),
            'animalOptions' => ServiceOptionLabels::options('animal_services'),
        ])
            ->layout('layouts::admin')
            ->title(__('admin-catalog.create.activity.title'));
    }

    protected function serviceCategory(): string
    {
        return 'attivita';
    }

    protected function photoDirectory(): string
    {
        return 'structure-photos';
    }

    /**
     * Valida e compone. È l'unico punto di validazione della pagina: il trait
     * chiama questo metodo prima di `collectPhotos()`, quindi un errore non
     * lascia foto su disco. Non esiste nessun `validateSections()`.
     *
     * La virgola italiana si normalizza QUI, prima del validate: '12,50' non
     * passa `numeric` (stesso trattamento di SmartboxPrice).
     */
    protected function draftAttributes(): array
    {
        $this->pricePerPerson = str_replace(',', '.', trim($this->pricePerPerson));

        // Tre argomenti, come nelle altre due famiglie: senza il terzo, gli
        // errori dei Form object citerebbero `location.address` e `info.date start`.
        $this->validate($this->rules(), $this->messages(), $this->validationAttributes());

        $filled = fn (array $translations): array => array_filter($translations, fn ($value) => filled($value));

        $attributes = [
            'type' => $this->type,
            'name' => $filled($this->name),
            ...$this->location->toDraft(),
            'description' => $filled($this->description),
            ...$this->info->toDraft(),
            ...$this->included->toDraft(),
            'animal_services' => $this->animalServices,
            'animal_services_other' => $filled($this->animalOther),
            'price_type' => $this->costType,
            'price_per_person' => $this->costType === 'pagamento' ? $this->pricePerPerson : null,
            'cancellation_when' => $this->when,
        ];

        if ($this->type === 'attivita') {
            $attributes['detailed_description'] = $filled($this->detailedDescription);
        }

        return $attributes;
    }
}
