# Stanze dentro la struttura — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Una struttura ha più stanze con mini-scheda propria, selezionabili nel dettaglio; per i partner Online la stanza ha un controllo di occupazione; i servizi creati «uno per camera» si accorpano.

**Architecture:** Nuova tabella `rooms` figlia di `structures`, popolata dal `StructurePublisher` a partire dal JSON `structure_drafts.rooms` (fonte di verità). La `Structure` resta il purchasable; la stanza viaggia come `options.room_id` nel carrello e come colonna `order_items.room_id` negli ordini. Un servizio `RoomOccupancy` conta le prenotazioni sovrapposte; `AvailabilityService` lo applica solo ai partner Online.

**Tech Stack:** Laravel 13, Livewire 4, Flux, spatie/laravel-translatable, PHPUnit (classi `Tests\TestCase`, `RefreshDatabase`).

**Spec:** `docs/specs/2026-10-08-stanze-struttura-design.md`

## Global Constraints

- PHP non gira sull'host: test con `wsl -d Ubuntu -e bash -lc '~/t.sh --filter=<Test>'`, suite con `wsl -d Ubuntu -e bash -lc '~/t.sh'`, stile con `wsl -d Ubuntu -e bash -lc '~/t.sh --pint --dirty'`.
- Ogni testo visibile passa da `__()` con chiave in `lang/it/*.php` **e** `lang/en/*.php` (`LangParityTest`).
- Foto: path sul disco public come `structures.gallery` (niente Media Library per il catalogo).
- Ordini che occupano una stanza: `OrderStatus::bookingStatuses()` (Paid, Confirmed), qualunque `payment_mode`.
- Controllo occupazione solo se `app(PartnerPaymentModeService::class)->forPurchasable($structure) === OrderPaymentMode::Online`.
- Notte del check-out esclusa ovunque (`booked_from < check_out` e `booked_until > check_in`).
- Commit: `tipo(scope): descrizione` in italiano, trailer `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`; prima di ogni commit skill `pre-commit-check`.

## Review Focus

1. **Struttura senza stanze** (catalogo demo/seeder, bozze legacy non ripubblicate): dettaglio, carrello, prezzo e ordine devono comportarsi esattamente come oggi → test in Task 4 e Task 7.
2. **`room_id` di un'altra struttura o inesistente** passato a mano nell'URL/Livewire → rifiutato con `CartValidationException::notPurchasable()` → test in Task 4.
3. **Stanza cancellata dal partner** con ordini storici e/o righe nel carrello → ordini leggibili (snapshot `room_name`, `room_id` null); la riga carrello fallisce in checkout con errore, non 500 → test in Task 3 e Task 5.
4. **Ripubblicazione della bozza** dopo una modifica: gli `id` delle stanze non cambiano (altrimenti l'occupazione si azzera) → test in Task 3.
5. **Partner che passa OnSite→Online** con ordini Confirmed sulle stesse date → la stanza risulta occupata → test in Task 2.

---

### Task 1: Schema e modello `Room`

**Files:**
- Create: `database/migrations/2026_10_08_100001_create_rooms_table.php`
- Create: `database/migrations/2026_10_08_100002_add_room_columns.php` (`order_items.room_id`, `structures.merged_into_structure_id`, `structure_drafts.merged_into_draft_id`)
- Create: `app/Models/Structure/Room.php`, `database/factories/Structure/RoomFactory.php`
- Modify: `app/Models/Structure/Concerns/StructureHasRelationships.php` (relazione `rooms()`), `app/Models/Order/OrderItem.php` (fillable + `room()`), `app/Providers/AppServiceProvider.php` (morph map `room` per le amenity)
- Test: `tests/Feature/Structure/RoomModelTest.php`

**Interfaces:**
- Produces: `Room` (`structure_id`, `draft_key` nullable uuid, `type`, `name` e `description` translatable, `price_cents`, `max_guests`, `max_animals`, `units` default 1, `photos` array|null, `position`); `Room::structure(): BelongsTo`; trait `HasAmenities`, `HasTranslations`; accessor `displayName(): string` (nome it oppure etichetta tipologia da `ServiceOptionLabels`); `Structure::rooms(): HasMany` ordinata per `position`; `OrderItem::room(): BelongsTo`; `RoomFactory` con state `units(int)`.
- Indici: unique (`structure_id`, `draft_key`); `order_items` index (`room_id`, `booked_from`, `booked_until`); `order_items.room_id` FK `nullOnDelete`; `rooms.structure_id` FK `cascadeOnDelete`.

- [ ] **Step 1: test che falliscono** — `test_structure_lists_rooms_by_position`, `test_room_display_name_falls_back_to_type_label` (name vuoto + type `doppia` ⇒ `__('...doppia')` da `ServiceOptionLabels`), `test_room_amenities_sync` (`$room->amenities()->sync(...)` persiste), `test_deleting_room_nulls_order_item_room_id`.
- [ ] **Step 2:** `~/t.sh --filter=RoomModelTest` → FAIL (classe mancante).
- [ ] **Step 3:** migrazioni, modello, factory, relazioni, morph map.
- [ ] **Step 4:** `~/t.sh --filter=RoomModelTest` → PASS.
- [ ] **Step 5:** commit `feat(catalog): tabella rooms e modello Room`.

### Task 2: `RoomOccupancy`

**Files:**
- Create: `app/Services/Availability/RoomOccupancy.php`
- Test: `tests/Unit/RoomOccupancyTest.php` (usa DB: `Tests\TestCase` + `RefreshDatabase`)

**Interfaces:**
- Consumes: `Room`, `OrderItem.room_id`, `OrderStatus::bookingStatuses()`.
- Produces:
  - `bookedUnits(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut, ?int $exceptOrderId = null): int` — numero massimo di unità occupate in una qualsiasi notte dell'intervallo (non la somma delle prenotazioni: due soggiorni consecutivi non sovrapposti fra loro occupano 1 unità).
  - `isAvailable(Room $room, CarbonImmutable $checkIn, CarbonImmutable $checkOut): bool` ⇔ `bookedUnits < units`.
  - `fullDates(Room $room, int $year, int $month): list<string>` ('Y-m-d' delle notti con occupazione ≥ units).

Algoritmo `bookedUnits`: carica gli `order_items` sovrapposti (`room_id`, `booked_from < checkOut`, `booked_until > checkIn`, ordine in bookingStatuses), poi per ogni notte `d` in [checkIn, checkOut) conta quelli con `booked_from <= d < booked_until`; ritorna il massimo.

- [ ] **Step 1: test che falliscono:**
  - `test_free_room_is_available`
  - `test_overlapping_paid_booking_fills_single_unit_room`
  - `test_checkout_day_of_other_booking_is_free` (prenotazione 10→12, richiesta 12→14 ⇒ disponibile)
  - `test_cancelled_and_pending_orders_do_not_count`
  - `test_confirmed_onsite_order_counts` (Review Focus 5)
  - `test_two_units_allow_two_overlapping_bookings_not_three`
  - `test_consecutive_bookings_occupy_one_unit` (10→12 e 12→14 su room units=1, richiesta 11→13 ⇒ bookedUnits = 1)
  - `test_full_dates_lists_nights_only` (10→12 ⇒ `['…-10','…-11']`)
- [ ] **Step 2:** `~/t.sh --filter=RoomOccupancyTest` → FAIL.
- [ ] **Step 3:** implementa il servizio.
- [ ] **Step 4:** → PASS.
- [ ] **Step 5:** commit `feat(availability): occupazione per stanza`.

### Task 3: Bozza → stanze pubblicate

**Files:**
- Modify: `app/Models/Structure/StructureDraft.php` (metodo `normalizedRooms()`), `app/Services/Partner/Publishing/StructurePublisher.php`, `app/Services/Partner/Publishing/FamilyPublisher.php` (`pruneReplacedPhotos` considera `rooms[].photos`), `app/Services/Partner/Publishing/DraftPublisher.php` e `AwaitingDraftPublisher.php` (saltano bozze con `merged_into_draft_id`), scope `listableFor` (esclude `merged_into_draft_id` non null)
- Test: `tests/Feature/Partner/Publishing/StructurePublisherRoomsTest.php`

**Interfaces:**
- Produces: `StructureDraft::normalizedRooms(): list<array{key:string,type:string,name:array,description:array,price:string,max_guests:int,max_animals:int,units:int,photos:list<string>,amenities:list<string>}>` — righe legacy: `count`→`units`, `key` = uuid generato **e salvato sulla bozza** (`saveQuietly`) così resta stabile; `max_guests` legacy per tipologia: singola 1, doppia 2, tripla 3, suite 4, intera_struttura = `beds`; `max_animals` legacy = 2.
- `StructurePublisher::publish()` dopo l'`updateOrCreate`: `syncRooms(Structure, StructureDraft): void` — `updateOrCreate` per (`structure_id`, `draft_key`), `syncAmenities($room, $row['amenities'])`, delete delle stanze con `draft_key` non più presente. Prezzo struttura = min `price` come oggi.

- [ ] **Step 1: test che falliscono:**
  - `test_publish_creates_one_room_per_draft_row` (campi copiati, cents)
  - `test_republish_keeps_room_ids_stable` (Review Focus 4)
  - `test_room_removed_from_draft_is_deleted_and_order_snapshot_survives` (Review Focus 3: `order_items.room_id` null, `options.room_name` intatto)
  - `test_legacy_rows_are_normalized_with_persisted_keys` (`{type:'doppia',count:3,price:'80'}` ⇒ units 3, max_guests 2, chiave identica a due chiamate)
  - `test_room_photos_are_not_pruned_while_referenced`
  - `test_merged_draft_is_not_listed_nor_published`
- [ ] **Step 2:** `~/t.sh --filter=StructurePublisherRoomsTest` → FAIL.
- [ ] **Step 3:** implementa.
- [ ] **Step 4:** → PASS, più `~/t.sh --filter=Publishing` verde.
- [ ] **Step 5:** commit `feat(partner): pubblica le stanze della bozza`.

### Task 4: Prezzo e validazione carrello per stanza

**Files:**
- Modify: `app/Services/Pricing/BookingPricingService.php` (`structureQuote`), `app/Services/Availability/AvailabilityService.php` (`ensureStructureAvailable`; nuovo `unavailableDates`)
- Test: `tests/Feature/Cart/RoomCartTest.php`

**Interfaces:**
- Consumes: `RoomOccupancy` (Task 2), `PartnerPaymentModeService::forPurchasable()`.
- Produces:
  - `AvailabilityService::roomFor(Structure $structure, array $options): ?Room` — null se la struttura non ha stanze; altrimenti `options.room_id` obbligatorio e della struttura, altrimenti `CartValidationException::notPurchasable()`.
  - In `ensureStructureAvailable`: con stanza, `BookingPricingService::persons($options) <= max_guests` e animali `<= max_animals` (altrimenti `CartValidationException::invalidParticipants()`); poi i controlli attuali; poi, se partner Online, `RoomOccupancy::isAvailable` (altrimenti `unavailableDates()`).
  - `AvailabilityService::unavailableDates(Structure $structure, ?Room $room, int $year, int $month): list<string>` = `closedDates` ∪ (Online && room ? `fullDates` : []).
  - `structureQuote`: `($room?->price_cents ?? $structure->price_cents) * nights + supplemento` (stesso `roomFor`).
- Il servizio `family 'service'` non cambia.

- [ ] **Step 1: test che falliscono:**
  - `test_structure_without_rooms_behaves_as_before` (Review Focus 1: addItem senza room_id ok, prezzo = structure)
  - `test_room_id_required_when_structure_has_rooms`
  - `test_room_of_another_structure_is_rejected` (Review Focus 2)
  - `test_price_uses_room_price`
  - `test_guests_over_room_capacity_rejected`
  - `test_online_partner_full_room_rejected` (`actingAsPayablePartner` per il proprietario; ordine Paid sovrapposto)
  - `test_onsite_partner_full_room_still_accepted` (`actingAsOfflinePartner`)
  - `test_unavailable_dates_merges_closures_and_full_nights`
- [ ] **Step 2:** `~/t.sh --filter=RoomCartTest` → FAIL.
- [ ] **Step 3:** implementa.
- [ ] **Step 4:** → PASS; `~/t.sh --filter=Cart` e `--filter=PricingTest` e `--filter=AvailabilityTest` verdi.
- [ ] **Step 5:** commit `feat(cart): stanza nel carrello con prezzo e disponibilità`.

### Task 5: Checkout — lock stanza e riga ordine

**Files:**
- Modify: `app/Pipes/Order/ReserveAvailabilityPipe.php`, `app/Pipes/Order/CreateOrderItemsPipe.php`
- Test: `tests/Feature/Orders/RoomCheckoutTest.php` (usa `Tests\Feature\Orders\Concerns\PlacesOrders`)

**Interfaces:**
- `ReserveAvailabilityPipe::ensureStructureAvailable`: se `options.room_id` presente, `Room::whereKey(...)->where('structure_id', ...)->lockForUpdate()->first()`; null ⇒ `CartValidationException::notPurchasable()`; poi `AvailabilityService::ensureAvailable`. L'ordinamento delle righe resta per `type:purchasableId` (le stanze di una stessa struttura sono un solo partner, il carrello è single-partner).
- `CreateOrderItemsPipe`: `'room_id' => $item->options['room_id'] ?? null`; aggiunge `room_name` (`Room::displayName()`) dentro `options` della riga ordine.
- Partner bookings (`app/Livewire/Partner/Bookings/PartnerBookings.php` + view) e riepilogo ordine cliente mostrano `options.room_name` se presente (chiave lang `orders.room`).

- [ ] **Step 1: test che falliscono:**
  - `test_order_item_stores_room_id_and_name`
  - `test_second_checkout_on_full_room_fails_and_refunds` (partner Online, units 1: primo ordine ok, secondo ⇒ `CartValidationException`, nessun nuovo order_item, storno chiamato come nel test sold-out eventi)
  - `test_deleted_room_in_cart_fails_cleanly` (Review Focus 3: nessun 500)
  - `test_partner_bookings_show_room_name`
- [ ] **Step 2:** `~/t.sh --filter=RoomCheckoutTest` → FAIL.
- [ ] **Step 3:** implementa.
- [ ] **Step 4:** → PASS; `~/t.sh --filter=Orders` e `--filter=Checkout` verdi.
- [ ] **Step 5:** commit `feat(orders): stanza bloccata al checkout e salvata nell'ordine`.

### Task 6: Wizard step 5 e creazione admin

**Files:**
- Modify: `app/Livewire/Forms/HotelRoomsForm.php`, `app/Livewire/Partner/Structure/HotelRooms.php`, `resources/views/livewire/partner/structure/hotel-rooms.blade.php`, `app/Livewire/Admin/Catalog/StructureCreate.php` (+ view), `app/Services/Admin/Catalog/AdminServiceCreator.php`, `lang/{it,en}/partner.php`, `lang/{it,en}/admin-catalog.php`
- Create: `resources/views/livewire/partner/structure/partials/room-modal.blade.php` (condiviso con l'admin)
- Test: `tests/Feature/Partner/PartnerHotelRoomsTest.php` (aggiorna), `tests/Feature/Admin/Catalog/StructureCreateTest.php` (aggiorna)

**Interfaces:**
- `HotelRoomsForm::$rooms` righe nel formato di `normalizedRooms()` (Task 3); `fillFromDraft` usa `normalizedRooms()`.
- Regole: `rooms` min:1 (size:1 per casa vacanza); `rooms.*.type` required; `rooms.*.name.it` nullable max:80; `rooms.*.description.it` nullable max:1000; `rooms.*.price` invariata; `rooms.*.max_guests` integer 1..20; `rooms.*.max_animals` integer 0..10; `rooms.*.units` integer min:1 (in:1 per casa vacanza); `rooms.*.photos` array max:10; `rooms.*.amenities.*` in slug amenity esistenti.
- Componente `HotelRooms`: `public ?int $editing = null`, `public array $roomForm`, `$roomPhotos` (`WithFileUploads`, stesse regole mime/size di `HotelPhotos`); azioni `openRoom(?int $index)`, `saveRoom()`, `removeRoom(int $index)`, `moveRoom(int $index, int $direction)`. Lo stepper `count` attuale diventa il campo `units` dentro la modale.
- Elenco: card per stanza con prima foto, nome/tipologia, prezzo, ospiti/animali, unità.

- [ ] **Step 1: test che falliscono (aggiorna i test esistenti al nuovo formato):**
  - `test_save_room_from_modal_appends_row`, `test_edit_room_updates_row`, `test_remove_room`, `test_room_validation_errors` (prezzo vuoto, max_guests 0)
  - `test_room_photo_upload_stored_on_public_disk`
  - `test_whole_property_has_single_room_without_add_button`
  - `test_next_saves_rooms_in_draft_with_keys`
  - Admin: `test_admin_creates_structure_with_named_rooms` (struttura pubblicata con 2 `rooms`)
- [ ] **Step 2:** `~/t.sh --filter=PartnerHotelRoomsTest` e `--filter=StructureCreateTest` → FAIL.
- [ ] **Step 3:** implementa form, componente, modale, testi.
- [ ] **Step 4:** → PASS; `~/t.sh --filter=Partner` verde.
- [ ] **Step 5:** skill `verify-in-browser` sullo step 5 (aggiungi 2 stanze con foto, salva, riapri); poi commit `feat(partner): stanze con scheda propria nel wizard`.

### Task 7: Dettaglio struttura — scelta della camera

**Files:**
- Modify: `app/Livewire/Catalog/AnimalHolidayStructure.php`, `app/Livewire/Concerns/HasBookingCalendar.php`, `resources/views/livewire/catalog/animal-holiday-structure.blade.php`, `lang/{it,en}/catalog.php`
- Create: `resources/views/livewire/catalog/partials/room-picker.blade.php`
- Test: `tests/Feature/Catalog/StructureRoomsTest.php`

**Interfaces:**
- Componente: `#[Url(as: 'camera')] public ?int $roomId`; `selectRoom(int $id)`; `mount` sceglie la prima stanza disponibile per le date di default (`RoomOccupancy::isAvailable` solo se Online), altrimenti la prima per `position`; `bookingOptions()` aggiunge `room_id` quando la struttura ha stanze; `roomId` di un'altra struttura ⇒ si ignora e si usa il default.
- `HasBookingCalendar`: nuovo `protected function calendarRoom(): ?Room { return null; }`; `isClosedDay` / `closedCalendarDays` usano `AvailabilityService::unavailableDates($structure, $this->calendarRoom(), …)`; `guestsAtMax()` / `animalsAtMax()` usano `calendarRoom()?->max_guests ?? self::MAX_GUESTS` (idem animali). Nessun cambio di comportamento per chi non sovrascrive `calendarRoom()`.
- Vista: sezione «Scegli la camera» (solo con ≥ 2 stanze) prima dei servizi; card con foto (prima + «vedi tutte» riusando la galleria esistente), nome, tipologia, ospiti/animali max, descrizione, servizi della camera (`amenityRows`), prezzo/notte, bottone «Seleziona»/«Selezionata». Booking card: nome stanza e `price_cents` della stanza.

- [ ] **Step 1: test che falliscono:**
  - `test_room_picker_shown_with_two_rooms_hidden_with_one`
  - `test_select_room_changes_price_and_options`
  - `test_guest_stepper_capped_by_room_capacity`
  - `test_foreign_room_id_in_url_falls_back_to_default`
  - `test_calendar_disables_full_nights_for_online_partner_only`
  - `test_structure_without_rooms_page_unchanged` (Review Focus 1: `HolidayPagesTest` continua a passare)
  - `test_add_to_cart_sends_selected_room`
- [ ] **Step 2:** `~/t.sh --filter=StructureRoomsTest` → FAIL.
- [ ] **Step 3:** implementa.
- [ ] **Step 4:** → PASS; `~/t.sh --filter=Catalog` e `--filter=HolidayPagesTest` verdi.
- [ ] **Step 5:** skill `verify-in-browser` (desktop + mobile: selezione stanza, prezzo, calendario); commit `feat(catalog): scelta della camera nel dettaglio struttura`.

### Task 8: Preferiti e dettaglio servizio partner

**Files:**
- Modify: `app/Services/FavoriteService.php` (opzioni default ~:414), `app/Livewire/Partner/MyServices/PartnerServiceDetail.php` + `resources/views/livewire/partner/my-services/detail.blade.php`
- Test: `tests/Feature/Favorites/AddFavoriteToCartTest.php`, `tests/Feature/Partner/PartnerServiceDetailTest.php`

**Interfaces:**
- `FavoriteService`: le opzioni default per una struttura con stanze includono `room_id` della prima stanza disponibile alle date default (stessa regola del Task 7: estrarre `Structure::defaultRoomFor(CarbonImmutable $checkIn, CarbonImmutable $checkOut): ?Room` e usarla in entrambi).
- Dettaglio servizio partner: elenco stanze con nome, tipologia, prezzo, unità.

- [ ] **Step 1: test che falliscono:** `test_favorite_structure_with_rooms_added_to_cart_with_default_room`, `test_service_detail_lists_named_rooms`.
- [ ] **Step 2:** → FAIL.
- [ ] **Step 3:** implementa (e rifattorizza il Task 7 per usare `defaultRoomFor`).
- [ ] **Step 4:** → PASS.
- [ ] **Step 5:** commit `feat(catalog): stanza di default per preferiti e dettaglio partner`.

### Task 9: Accorpamento e redirect 301

**Files:**
- Create: `app/Console/Commands/MergeStructures.php` (firma `catalog:merge-structures {target} {sources*} {--dry-run}`), `app/Services/Admin/Catalog/StructureMerger.php`
- Modify: `app/Livewire/Catalog/AnimalHolidayStructure.php` (`mount`: redirect 301)
- Test: `tests/Feature/Admin/Catalog/MergeStructuresTest.php`

**Interfaces:**
- `StructureMerger::plan(Structure $target, Collection $sources): list<array>` (righe stanza che verrebbero aggiunte, per il dry-run) e `merge(Structure $target, Collection $sources): void` in `DB::transaction`:
  1. ogni sorgente diventa una riga in `target->draft->rooms`: `name.it` = testo dopo l'ultimo « - » del nome sorgente (o nome intero), `description` sorgente, `photos` = gallery sorgente, `price` = `price_from_cents/100`, `units` = somma `units` delle sue righe, `max_guests`/`max_animals` = massimo delle sue righe, `amenities` = slug delle amenity sorgente;
  2. `reviews` sorgente → `reviewable_id` = target;
  3. `order_items` sorgente → solo `room_id` = nuova stanza (dopo il publish, mappando per `draft_key`); `purchasable` resta la sorgente, così lo storico ordini non cambia;
  4. sorgente nascosta (moderazione) + `merged_into_structure_id`; bozza sorgente `merged_into_draft_id`;
  5. `StructurePublisher::publish($target->draft)`.
- Errori: target o sorgente senza bozza, partner diversi, target fra le sorgenti ⇒ il comando esce con errore e non scrive nulla.
- 301: `findBySlug` miss ⇒ `Structure::withHidden()->where('slug', …)->whereNotNull('merged_into_structure_id')`; se trovata, `redirect()->route('holiday.structure', [region del target, slug del target])` con status 301.

- [ ] **Step 1: test che falliscono:**
  - `test_dry_run_writes_nothing`
  - `test_merge_turns_sources_into_rooms` (scenario Casale: «X - Monia/Dona/Stella» ⇒ stanze «Monia», «Dona», «Stella»)
  - `test_merge_moves_reviews_and_order_items`
  - `test_merged_sources_hidden_and_drafts_not_listed`
  - `test_old_url_redirects_301_to_target`
  - `test_refuses_sources_of_another_partner`
- [ ] **Step 2:** `~/t.sh --filter=MergeStructuresTest` → FAIL.
- [ ] **Step 3:** implementa.
- [ ] **Step 4:** → PASS.
- [ ] **Step 5:** commit `feat(admin): accorpa le strutture create una per camera`.

### Task 10: Chiusura

- [ ] **Step 1:** `wsl -d Ubuntu -e bash -lc '~/t.sh --pint --dirty'` → nessuna modifica pendente.
- [ ] **Step 2:** `wsl -d Ubuntu -e bash -lc '~/t.sh'` → suite completa verde (incluso `LangParityTest`).
- [ ] **Step 3:** build asset se toccati CSS/JS (`npm run build`).
- [ ] **Step 4:** aggiorna `docs/piano-pubblicazione-partner.md:132` (inventario stanze: fatto per Online) e aggiungi a `docs/` un runbook breve per il deploy: migrazioni, poi `catalog:merge-structures <casale> <dona> <stella> --dry-run`, controllo, esecuzione reale, rinomina del target dall'admin.
- [ ] **Step 5:** commit `docs: stanze dentro la struttura, runbook accorpamento`.
