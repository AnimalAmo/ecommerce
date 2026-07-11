# Piano: pubblicazione servizi partner → catalogo B2C

**Goal**: allineare i dati che i partner inseriscono dalla dashboard per renderli fruibili
agli utenti finali dell'ecommerce. Oggi i draft completati sono un vicolo cieco: il B2C legge
solo le tabelle seedate. Audit completo dei gap (15 finding, 5 blocker) nella memoria di
progetto `partner-b2c-publication-gap` — questo piano li risolve.

Stato: 2026-07-10 sera. Riprendere da "Step B".

---

## Fatto (committato)

- `f94eff0` — testi liberi partner localizzati it/en (spatie/laravel-translatable su
  structure_drafts: name, description, detailed_description, meeting_point, additional_other,
  animal_services_other) + tab IT/EN (`x-partner.locale-tabs`) su tutti gli step testuali.
  Fallback contenuti EN→IT via `Translatable::fallback()` in AppServiceProvider.
- `b0bd5da` — Flux Pro installato (auth.json da matsuri, gitignored); select Provincia
  listbox (tabella `provinces` da locations-data + seeder); time-picker check-in/out.
- `5c6935e` — **fondamenta pipeline** (migrate + seed già applicati sulla VM):
  - `provinces.region_id` + mappa ISTAT 107→20 in `ProvinceSeeder::REGION_PROVINCES`
  - `structures`/`events`/`smartbox_packages`: `user_id`, `structure_draft_id` (unique),
    `cancellation_policy_days`; `structures.rating` nullable
  - concern `App\Models\Concerns\HasCatalogImages` (imageUrl/heroImageUrl/mapImageUrl:
    path con '/' → Storage public URL, altrimenti `asset('img/xd/<stem>.jpg')`)

## Step B — Model catalogo (IN CORSO, nessun file toccato)

Su `Structure`, `Event`, `SmartboxPackage`:
1. `use HasCatalogImages` + `use Spatie\Translatable\HasTranslations`
2. `public array $translatable`: Structure `[name, description]`; Event `[title, description]`;
   SmartboxPackage `[title, description, extended_description]`.
   ⚠️ SOLO colonne stringa — MAI json (general_info/features): spatie interpreta un array
   assegnato come mappa di traduzioni (le chiavi diventerebbero locale). I seeder che
   assegnano stringhe restano compatibili (string → traduzione del locale corrente).
3. fillable + `user_id`, `structure_draft_id`, `cancellation_policy_days`
4. relazioni `user(): BelongsTo(User)` e `draft(): BelongsTo(StructureDraft, 'structure_draft_id')`
5. Rischio test: eventuali `assertDatabaseHas` su name/title/description raw (ora JSON) —
   convertire a letture model + `getTranslation()`. CatalogSeedTest passa (già smoke-testato
   post-migration), ma la conversione translatable dei model catalogo NON è ancora fatta:
   ricontrollare la suite dopo.

## Step C — Sweep blade catalogo

1. Sostituire OGNI `asset('img/xd/'.$x->img.'.jpg')` (e hero_img/map_img) con
   `$x->imageUrl()` / `heroImageUrl()` / `mapImageUrl()`. Call site (grep `img/xd`):
   catalog blades (animal-holiday*, events, event-detail, activity-detail, smartbox,
   smartbox-detail), cart (`CartItemData.php:93` — è una classe, non blade!, Cart.php:385),
   favorites (`partials/favorite-card.blade.php`). Region img resta com'è (piattaforma).
2. Guard rating null: star-loop in `animal-holiday-structure.blade.php:139-148` (+ listing
   cards se mostrano stelle) → wrappare in `@if($structure->rating !== null)` con stato
   "Nuovo" altrimenti. `map_img` null → nascondere sezione mappa con `@if`.
3. `Format::money()` è type-hinted int — nessun cambiamento se il publisher scrive cents.

## Step D — Publisher (cuore)

`app/Services/Partner/Publishing/`:
- `DraftPublisher::publish(StructureDraft $draft): Model` — dispatcher su `$draft->family()`
  → `StructurePublisher` | `EventPublisher` | `SmartboxPublisher`. Ogni publisher:
  `updateOrCreate(['structure_draft_id' => $draft->id], [...])`.
- Wire: in `InteractsWithStructureDraft::completeDraft()` dopo l'update status →
  `app(DraftPublisher::class)->publish($draft->fresh())`. MVP = pubblicazione automatica;
  docblock: la spec prevede approvazione superadmin → futuro gate `status='approved'`.

Mappature comuni:
- `user_id` = draft->user_id; `slug` = `Str::slug(name it).'-'.$draft->id` (stabile, unico)
- name/title/description: **passare le traduzioni** — `$draft->getTranslations('name')` →
  assegnazione array su colonna translatable del catalogo
- `location` = `$draft->locationLabel()`; `cancellation_policy_days` = (int) cancellation_when
- img/hero_img = `$draft->photos[0] ?? null` (path storage: HasCatalogImages li risolve);
  map_img = null
- `position` = max(position)+1 della tabella target

`StructurePublisher` (famiglia struttura/hotel):
- type: hotel|bb|agriturismo → `ProductType::Structure` (sotto-tipologia per ora persa —
  v2: colonna `sub_type`)
- `region_id` = `Province::where('short_name', $draft->province)->value('region_id')`
  (richiesto: wizard valida provincia → sempre risolvibile)
- `price_cents` = `price_from_cents` = min(rooms[].price)*100 ("a partire da");
  `animal_supplement_cents` = 0; `rating` = null
- `general_info` = sintesi [{icon,title,lines[]}] (shape esatta in
  `StructureSeeder::hotelGeneralInfo()` + partial `partials/general-info.blade.php`):
  - cancellation_when → `['icon'=>'calendar-return','title'=>"Cancellazione gratuita fino a N giorni prima",'lines'=>[...]]`
  - meals selezionati (nessuno escluso) + meal_times → icon coffee (colazione) / lunch
    (pranzo+cena), lines "Orario: HH:MM-HH:MM"
  - checkin/checkout → icon home, lines "Check-in: .. · Check-out: .."
  - Le stringhe dentro general_info restano it-only (limite noto, v2)
- amenities: sync pivot amenityables per gruppo con `included` bool. Mappa slug wizard →
  nome Amenity (AmenitySeeder): hotel: `wifi→Wifi`, `aria_condizionata→Aria condizionata
  negli spazi comuni`, `sauna→Spa`(approx), pranzo in meals→`Pranzo`; animal:
  `pet_sitting→Pet sitting`, `veterinario→Servizio veterinario`, `omaggio→Omaggio di
  benvenuto`. Slug non mappati → ignorati; amenity del gruppo non selezionate →
  `included=false` (riproduce le righe ✗ del template). `features` = null (guard blade).

`EventPublisher` (attivita|eventi):
- type: attivita→Activity, eventi→Event
- `starts_at` = date_start + (time_start ?: 00:00); `ends_at` = date_end + (time_end ?: 23:59)
- attività: `duration_days` = diff giorni +1 (altrimenti il detail fa fallback mock '3 giorni')
- prezzo: price_type gratuito → `is_free=true, price_cents=null`; pagamento →
  `price_cents=(int) round(price_per_person*100)` (normalizzare virgola: str_replace ',','.')
- venue: `Venue::firstOrCreate(['name' => meeting_point it (o name)], ['address' =>
  indirizzo composto])` → venue_id. ⚠️ guard nei blade detail per venue null comunque
- `max_participants` = null (capienza illimitata; TODO input wizard — audit finding 11)

`SmartboxPublisher` (soggiorno|benessere|avventura):
- type: soggiorno→Stay, benessere→Wellness, avventura→Adventure
- title/description da traduzioni; `extended_description` = detailed_description (traduzioni)
- `price_cents` = parse(price)*100 (⚠️ PRIMA fix validazione `SmartboxPrice`:
  `['required','numeric','min:0']` al posto di string max:32 — oggi accetta "215 €");
  `price_from_cents` = price_cents; `validity_months` = 12 (regola piattaforma);
  audience/audience_people = null (guard blade se serve)
- `general_info` = sintesi (cancellazione + duration_days "Soggiorno di N giorni" + meals)
- smartbox_structures: NON pubblicare (oggi slug demo hardcoded — vedi audit finding 5;
  v2: opzioni = strutture completate del partner + pivot package↔structures)

## Step E — Test + verify

1. Feature test per publisher (nuovo file per famiglia): completare un draft finto →
   assert riga catalogo con type/slug/region/cents/general_info/amenities/traduzioni;
   ri-pubblicazione aggiorna la stessa riga (structure_draft_id unique).
2. e2e: `PartnerHotelPaymentTest`-style — completeDraft → `Structure::where(...)exists()`;
   GET pagina B2C regione → assertSee nome struttura; detail per slug → 200.
3. Suite piena su VM + Pint. Verify live: login partner demo → completare wizard hotel →
   struttura visibile su animalamo.test (listing regione + detail).
4. ⚠️ Noto: `AnimalHolidayRegion` mostra TUTTE le strutture in ogni regione (fedeltà mock,
   commento esplicito nel component) — con dati partner va cambiato in
   `where('region_id', ...)`; da confermare col cliente perché altera il mock.

## Fuori scope MVP (v2, dal report audit)

- Moderazione superadmin (spec) al posto dell'auto-publish
- Inventario stanze reale (tabella rooms + allocazione) — oggi solo structure_closures,
  overbooking possibile; UI partner per gestire chiusure
- Input wizard mancanti: capienza eventi, supplemento animali, audience smartbox
- general_info multilingua; sub-type struttura (B&B/agriturismo oggi appiattiti su Structure)
- smartbox ↔ strutture reali (pivot + consenso hotel `smartbox_consent` oggi mai letto)
- FAQ/features partner; foto gallery (CTA "vedi tutte le foto" è TODO template)
- Cap description 200→1000+; cart "Cancellazione gratuita" statico → usare
  cancellation_policy_days per riga
