<?php

namespace App\Livewire\Forms;

use App\Models\Structure\StructureDraft;
use App\Services\Partner\ServiceOptionLabels;
use App\Support\Translations;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Attività/Eventi — step 6 "Cosa è incluso". Servizi presenti + aggiuntivi
 * (con orari pasti) + regole della struttura.
 *
 * NB: il campo "regole" è esposto come `structureRules` per non collidere con il
 * metodo `rules()` di Livewire\Form; su bozza resta la colonna `rules`.
 */
class ActivityIncludedForm extends Form
{
    /** Servizi presenti (multi-scelta). */
    public array $services = [];

    /** Servizi aggiuntivi presenti (multi-scelta). */
    public array $additional = [];

    /** Dettaglio per "Altro" servizio aggiuntivo (localizzato it/en). */
    public array $additionalOther = ['it' => '', 'en' => ''];

    /** Orari (inizio/fine) per i pasti: colazione | pranzo | cena. */
    public array $mealTimes = [
        'colazione' => ['from' => '', 'to' => ''],
        'pranzo' => ['from' => '', 'to' => ''],
        'cena' => ['from' => '', 'to' => ''],
    ];

    /** Regole della struttura (multi-scelta). Colonna bozza: `rules`. */
    public array $structureRules = [];

    /**
     * Ogni voce delle tre liste contro la mappa da cui la vista la disegna
     * (ServiceOptionLabels, gruppi `services`, `additional`, `rules`), come fa
     * già il pannello admin. Senza `in:` un payload manomesso scriveva nella
     * bozza slug che nessun publisher sa tradurre e che nessuno step disegna.
     */
    public function rules(): array
    {
        return [
            'services' => ['array'],
            'services.*' => ['string', Rule::in(ServiceOptionLabels::slugs('services'))],
            'additional' => ['array'],
            'additional.*' => ['string', Rule::in(ServiceOptionLabels::slugs('additional'))],
            'additionalOther.it' => ['nullable', 'string', 'max:200'],
            'additionalOther.en' => ['nullable', 'string', 'max:200'],
            'mealTimes.*.from' => ['nullable', 'string'],
            'mealTimes.*.to' => ['nullable', 'string'],
            'structureRules' => ['array'],
            'structureRules.*' => ['string', Rule::in(ServiceOptionLabels::slugs('rules'))],
        ];
    }

    /**
     * Le tre liste si rileggono filtrate sugli slug del gruppo: una bozza può
     * averne uno che il wizard non disegna (DemoUserSeeder semina 'parcheggio'),
     * e con l'`in:` di rules() il partner resterebbe fermo su un errore senza
     * una casella da togliere, né un posto dove leggerlo. Si perde solo alla
     * prossima risalvata, ed è una voce che la scheda pubblica non ha mai
     * mostrato: i publisher ignorano gli slug fuori mappa.
     */
    public function setFromDraft(StructureDraft $draft): void
    {
        $this->services = self::known('services', $draft->services);
        $this->additional = self::known('additional', $draft->additional_services);
        $this->additionalOther = array_merge(['it' => '', 'en' => ''], $draft->getTranslations('additional_other'));
        $this->mealTimes = $draft->meal_times ?: $this->mealTimes;
        $this->structureRules = self::known('rules', $draft->rules);
    }

    /** Attributi nel formato colonne della bozza (snake_case). */
    public function toDraft(): array
    {
        return [
            'services' => $this->services,
            'additional_services' => $this->additional,
            'additional_other' => Translations::replacing($this->additionalOther),
            'meal_times' => $this->mealTimes,
            'rules' => $this->structureRules,
        ];
    }

    /** Gli slug di `$values` che il gruppo conosce, nell'ordine della bozza. */
    private static function known(string $group, ?array $values): array
    {
        return array_values(array_intersect($values ?? [], ServiceOptionLabels::slugs($group)));
    }
}
