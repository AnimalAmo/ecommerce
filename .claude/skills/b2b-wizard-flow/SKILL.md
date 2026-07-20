---
name: b2b-wizard-flow
description: Quando si aggiunge o modifica uno step di un wizard partner B2B AnimalAmo (flussi Structure/hotel, Activity, Smartbox, Registration) o si tocca la pubblicazione draft→catalogo B2C. Copre il pattern step-class + trait draft, le route localizzate, i Form object, i Publisher e il pattern di test.
---

# Wizard partner B2B: pattern del progetto

Tre flussi di creazione servizio + registrazione, ognuno una catena di step. Ultimo step: hotel = 11, attività = 10, smartbox = 12.

## Anatomia di uno step

Ogni step è un componente Livewire in `app/Livewire/Partner/<Flusso>/` (es. `Structure/HotelTitle.php`) che:

1. usa il trait `InteractsWithStructureDraft` (`app/Livewire/Concerns/`);
2. in `mount()` idrata le proprietà dalla bozza: `$this->draft()` (creata/lucata via sessione `structure_draft_id`);
3. in `next()` valida, poi `$this->saveStep([...campi...], <numero step>)` e `$this->redirectRoute('partner.<flusso>.<step successivo>')`. `saveStep` avanza `current_step` solo in avanti;
4. l'ULTIMO step chiama `$this->completeDraft(<finalStep>)`: transazione che marca la bozza `completed` e la pubblica sul catalogo B2C via `DraftPublisher` (MVP: pubblicazione automatica, la moderazione superadmin arriverà come gate a monte).

Campi tradotti: proprietà array `['it' => '', 'en' => '']`, `it` obbligatorio, `en` opzionale; salva con `array_filter` così su EN scatta il fallback IT (spatie/laravel-translatable). Messaggi di validazione da `lang/it/partner.php` (`__('partner.<step>.error_…')`).

Validazione complessa/riusata → Form object in `app/Livewire/Forms/` (es. `HotelLocationForm`, `SmartboxMealsForm`) invece di regole inline.

## Route

In `routes/web.php`, blocco partner: gli step del wizard sono PUBBLICI (fuori dal middleware `['auth', 'partner']` — la bozza vive in sessione prima del login); l'area riservata (dashboard, i miei servizi, prenotazioni, profilo) è dentro il middleware.

```php
Route::get(LaravelLocalization::transRoute('routes.partner.structure.hotel.title'), PartnerHotelTitle::class)
    ->name('partner.structure.hotel.title');
```

Nuovo step = route qui + slug tradotto in `lang/{it,en}/routes.php` + view in `resources/views/livewire/partner/<flusso>/`. Link sempre con `route()`, mai href hardcoded.

## Aggiungere uno step in mezzo a un flusso

1. Crea componente + view + route + slug localizzati.
2. Aggiorna il `redirectRoute` dello step precedente e i numeri passati a `saveStep`/`completeDraft` degli step a valle (i numeri sono posizionali).
3. Se il campo nuovo va sul catalogo B2C, estendi il Publisher del flusso.
4. Test dedicato (sotto) + aggiorna eventuali test di flusso completo.

## Pubblicazione draft → B2C

`app/Services/Partner/Publishing/`: `DraftPublisher::publish(StructureDraft)` smista a `StructurePublisher` / `EventPublisher` / `FamilyPublisher` / `SmartboxPublisher` in base al tipo. Ogni publisher mappa i campi bozza sulle entità catalogo (gerarchia geografica inclusa) e risolve le immagini. Nuovo campo bozza visibile sul B2C = mapping nel publisher giusto + test in `tests/Feature/Partner/Publishing/`.

## Test

Un file per step in `tests/Feature/Partner/` (es. `PartnerHotelTitleTest.php`). Pattern di `PartnerMyServicesTest`: helper privati che creano bozze nello stato voluto, poi `Livewire::test(Componente::class)` con assert su validazione, salvataggio bozza e redirect. Ricorda: i test girano SOLO nella VM (skill `pre-commit-check`).
