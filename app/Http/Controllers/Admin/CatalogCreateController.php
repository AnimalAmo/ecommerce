<?php

namespace App\Http\Controllers\Admin;

/**
 * Una sola rotta `catalog/new/{family}` per le quattro voci di "Crea scheda",
 * come chiede il contratto: il nome `admin.catalog.create` deve restare uno
 * solo, perché la card partner di UserShow e la modale del catalogo lo
 * costruiscono con `['family' => ...]`. Tre nomi diversi obbligherebbero
 * chi linka a sapere quale componente serve.
 *
 * Un componente Livewire è un controller invocabile (HandlesPageComponents::
 * __invoke), quindi qui basta risolverlo e richiamarlo: i parametri di rotta
 * arrivano al mount() come in una registrazione diretta.
 *
 * L'ordine delle chiavi è quello di AdminServiceCreator::CREATABLE_FAMILIES,
 * ed è l'ordine del menù: `array_keys(self::COMPONENTS)` e la costante devono
 * raccontare la stessa cosa.
 *
 * I nomi delle classi sono STRINGHE, non `::class` con un `use` in testa:
 * ActivityCreate e SmartboxCreate nascono nei Task 6 e 8, e fino ad allora
 * `app($component)` su una classe assente non darebbe un 404 ma un fatale
 * «Class not found». Il `class_exists()` qui sotto rende quel buco un 404 vero
 * — e quando i due componenti esistono non c'è nessun secondo giro da fare.
 */
class CatalogCreateController
{
    /** Famiglia dell'URL => nome completo del componente del pannello. */
    public const COMPONENTS = [
        'structure' => 'App\Livewire\Admin\Catalog\StructureCreate',
        'service' => 'App\Livewire\Admin\Catalog\StructureCreate',
        'activity' => 'App\Livewire\Admin\Catalog\ActivityCreate',
        'smartbox' => 'App\Livewire\Admin\Catalog\SmartboxCreate',
    ];

    public function __invoke(string $family)
    {
        $component = self::COMPONENTS[$family] ?? null;

        abort_if($component === null || ! class_exists($component), 404);

        return app($component)();
    }
}
