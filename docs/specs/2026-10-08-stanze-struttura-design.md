# Stanze dentro la struttura — design

Data: 08/10/2026 · Stato: in revisione

## Richiesta della cliente

> Quando una struttura inserisce più stanze, queste non devono comparire come
> singoli risultati ognuna con la sua pagina, ma devono essere raggruppate per
> struttura; nel dettaglio struttura si seleziona la stanza con le sue
> caratteristiche.

Caso reale (08/10/2026, `/animal-holiday/lombardia`): tre schede distinte
«Il Casale Sotto le Stelle - Monia / - Dona / - Stella», stessa località,
tutte «a partire da 100 €».

## Diagnosi

Il codice non produce un risultato per stanza: una bozza (`structure_drafts`)
pubblica una sola `Structure` (`StructurePublisher`). Le stanze sono un JSON
`rooms` sulla bozza (`{type, count, price}`), di cui a catalogo arriva solo il
prezzo minimo. Una stanza non può avere nome, foto, capienza o servizi propri,
quindi il partner ha creato **un servizio per camera**.

Servono quindi (1) stanze vere dentro la struttura e (2) l'accorpamento dei
servizi già creati così.

## Decisioni prese

| Tema | Decisione |
|---|---|
| Scheda stanza | Mini-scheda completa: nome, tipologia, descrizione, foto, prezzo/notte, ospiti max, animali max, servizi della camera, unità |
| Oggetto prenotabile | Resta la `Structure` (morph `structure`); la stanza viaggia come `room_id` |
| Disponibilità, partner **Online** | Controllo occupazione per stanza: prenotazioni sovrapposte ≥ `units` ⇒ non disponibile |
| Disponibilità, partner **OnSite** | Come oggi: solo chiusure manuali della struttura |
| Ordini che occupano | `OrderStatus::bookingStatuses()` (Paid, Confirmed) — **anche quelli OnSite**, così il passaggio OnSite→Online non apre doppie prenotazioni |
| Servizi già creati uno per camera | Accorpati con un comando admin, vecchi URL in 301 |

Fuori perimetro: inventario per Smartbox, iCal, filtro capienza nella ricerca,
chiusure per singola stanza (restano a livello struttura).

## 1. Modello dati

### Tabella `rooms` (nuova) — `App\Models\Structure\Room`

| Colonna | Tipo | Note |
|---|---|---|
| `id` | pk | |
| `structure_id` | fk → structures, cascade | |
| `draft_key` | uuid, nullable | identità stabile della riga nel JSON della bozza: il publisher fa `updateOrCreate` su (`structure_id`, `draft_key`), così l'`id` non cambia a ogni ripubblicazione e gli ordini restano agganciati |
| `type` | string | `singola|doppia|tripla|suite|intera_struttura` (etichette da `ServiceOptionLabels`) |
| `name` | json translatable | es. «Monia»; vuoto ⇒ si mostra l'etichetta della tipologia |
| `description` | json translatable, nullable | |
| `price_cents` | unsigned int | per notte |
| `max_guests` | unsigned tinyint | |
| `max_animals` | unsigned tinyint | |
| `units` | unsigned smallint, default 1 | camere identiche; eredita il `count` attuale |
| `photos` | json, nullable | path sul disco public, come `structures.gallery` (il catalogo non usa Media Library) |
| `position` | unsigned smallint | ordine di visualizzazione |
| timestamps | | |

Trait: `HasAmenities` (polimorfico, già esistente) per i servizi della camera,
`HasTranslations`. Relazione `Structure::rooms()` ordinata per `position`.

### Bozza: `structure_drafts.rooms` (JSON esteso)

Ogni riga diventa:
`{key (uuid), type, name{it,en}, description{it,en}, price, max_guests,
max_animals, units, photos[], amenities[]}`.
Le righe esistenti vengono normalizzate in lettura (`count` → `units`, `key`
generata), senza migrazione distruttiva.

### Ordini: `order_items.room_id` + snapshot

- `order_items.room_id` nullable fk → rooms (`nullOnDelete`), indice
  composto (`room_id`, `booked_from`, `booked_until`) per la query di occupazione.
- Lo snapshot dell'ordine aggiunge il nome della stanza (`options.room_name`),
  così mail, prenotazioni partner e storico restano leggibili anche se la
  stanza viene poi cancellata.

### Carrello

`room_id` vive in `cart_items.options` (canonicalizzato da
`CartManager::canonicalize`): il carrello ha due driver (DB e sessione) e
nessuna query per stanza, quindi una colonna non serve. Viene copiato nella
colonna `order_items.room_id` da `CreateOrderItemsPipe`.

### Struttura

- `price_cents` / `price_from_cents` = stanza più economica (come oggi).
- `structures.merged_into_structure_id` nullable: serve al 301 dopo
  l'accorpamento (§5).

## 2. Wizard partner (step 5 «Stanze») e pubblicazione

- Lo step 5 (`HotelRooms` + `HotelRoomsForm`) diventa un elenco di card stanza
  con «Aggiungi stanza» / «Modifica» / «Elimina» / riordino. Il dettaglio si
  modifica in una modale (Flux) con i campi della mini-scheda, upload foto
  (stesse regole di `HotelPhotos`) e checklist dei servizi camera (stesse
  amenity del catalogo).
- Validazione: almeno una stanza; prezzo > 0; `max_guests` ≥ 1; `units` ≥ 1.
- **Casa vacanza** (`intera_struttura`): una sola stanza, senza «Aggiungi
  stanza», come oggi.
- `StructureCreate` dell'admin riusa `HotelRoomsForm` e riceve la stessa UI.
- `StructurePublisher`: dopo l'`updateOrCreate` della struttura sincronizza le
  stanze per `draft_key` (crea/aggiorna, cancella quelle tolte dalla bozza) e le
  loro amenity. Le stanze cancellate con ordini storici restano negli ordini
  grazie allo snapshot e al `nullOnDelete`.
- Pulizia foto: `pruneReplacedPhotos` considera anche le foto delle stanze,
  per non cancellare file ancora referenziati dalla versione pubblicata.
- Partner «I miei servizi» → dettaglio servizio: l'elenco stanze mostra nome,
  tipologia, prezzo e unità.

## 3. Catalogo B2C

### Lista regione
Nessun cambio di query: una card per struttura, «a partire da» = stanza più
economica. Il raggruppamento è un effetto del modello (e dell'accorpamento dei
dati esistenti), non un `GROUP BY`.

### Dettaglio struttura (`AnimalHolidayStructure`)
- Nuova sezione **«Scegli la camera»** sopra i servizi: una card per stanza con
  foto (slider / «vedi tutte»), nome, tipologia, ospiti e animali max,
  descrizione, servizi della camera, prezzo/notte e pulsante «Seleziona».
- Proprietà Livewire `#[Url] roomId`; default = la prima stanza disponibile per
  le date correnti, altrimenti la prima per `position`.
- Booking card: mostra la stanza selezionata e il suo prezzo; gli stepper
  ospiti/animali sono limitati da `max_guests` / `max_animals`.
- Calendario: date chiuse = chiusure struttura ∪ (solo partner Online) giorni in
  cui la stanza selezionata è piena. Cambia al cambio stanza.
- Una sola stanza ⇒ niente sezione di scelta: la stanza è implicita, la pagina
  resta come oggi.
- Strutture senza stanze (catalogo demo / seeder): comportamento attuale,
  `room_id` nullo, prezzo della struttura.

### Preferiti
`FavoriteService`: le opzioni di default per «aggiungi al carrello» includono la
prima stanza disponibile.

## 4. Prezzo e disponibilità

### Prezzo
`BookingPricingService::structureQuote`: se `options.room_id` è valorizzato usa
`room.price_cents` (stessa formula notti + supplemento animali), altrimenti
`structure.price_cents`.

### Validazione opzioni (`AvailabilityService::ensureStructureAvailable`)
Se la struttura ha stanze:
1. `room_id` obbligatorio e appartenente alla struttura;
2. ospiti ≤ `max_guests`, animali ≤ `max_animals`;
3. controlli attuali (passato, intervallo, chiusure);
4. **solo se il partner proprietario ha `paymentMode() === Online`**: occupazione.

### Occupazione
`RoomOccupancy` (nuovo servizio, `app/Services/Availability/`):

```
bookedUnits(room, checkIn, checkOut) =
  count order_items
   where room_id = room
     and booked_from  <  checkOut
     and booked_until >  checkIn
     and order.status in OrderStatus::bookingStatuses()
```

Disponibile se `bookedUnits < units`. Notte del check-out esclusa, come per le
chiusure. `fullDates(room, year, month)` alimenta il calendario.

Il controllo gira in tre punti:
- **add-to-cart / modifica riga** (`CartManager` → `AvailabilityService`):
  senza lock, per dare subito l'errore;
- **calendario** del dettaglio: giorni pieni disabilitati;
- **checkout** (`ReserveAvailabilityPipe`): `Room::lockForUpdate()` sulla riga
  stanza, poi ricontrollo nella transaction. Righe ordinate per tipo + id stanza,
  come già fatto per gli eventi (niente deadlock). Stanza piena ⇒
  `CartValidationException::unavailableDates()` ⇒ storno dell'incasso, come per
  il sold-out evento.

La serializzazione regge perché l'ordine nasce Pending e passa a Paid nella
stessa transaction (capture-first): il secondo checkout attende il lock e vede
l'ordine del primo già committato.

## 5. Accorpamento dei servizi esistenti

Comando `catalog:merge-structures {target} {sources*} {--dry-run}` (id
struttura). La fonte di verità è la **bozza**: se si toccasse solo la tabella
`structures`, la prossima ripubblicazione del target cancellerebbe le stanze.

Per ogni sorgente:
1. aggiunge alla bozza target una riga `rooms` con nome = suffisso dopo
   l'ultimo « - » del nome sorgente (es. «Monia»; altrimenti il nome intero),
   descrizione, foto, prezzo minimo, `units` = somma delle unità, amenity;
2. sposta le recensioni (`reviewable`) sulla struttura target;
3. collega gli `order_items` storici alla stanza nuova (`room_id`);
4. nasconde la struttura sorgente (moderazione) e imposta
   `merged_into_structure_id`; sulla bozza sorgente imposta la nuova colonna
   `structure_drafts.merged_into_draft_id`, esclusa dallo scope
   `listableFor` (oggi le bozze non hanno uno stato archiviato), così non
   compare più in «I miei servizi» e non viene ripubblicata;
5. ripubblica il target (`StructurePublisher`).

Il nome della struttura target va ripulito a mano dall'admin (es. «Il Casale
Sotto le Stelle»). `--dry-run` stampa l'anteprima senza scrivere. Tutto in una
transaction.

**301**: `AnimalHolidayStructure` quando lo slug non trova una struttura
visibile cerca con `withHidden()`; se ha `merged_into_structure_id` reindirizza
(301) alla struttura target.

## 6. Testi e lingue

Tutti i testi nuovi passano per `__()` con chiavi in `lang/it` e `lang/en`
(`LangParityTest`).

## 7. Test

- **Unit**: `RoomOccupancy` (sovrapposizioni ai bordi: check-out = check-in di
  un'altra prenotazione è libero; Cancelled non conta; Confirmed conta; units > 1).
- **Publisher**: sync stanze per `draft_key` (id stabile, stanza tolta
  cancellata, amenity), prezzo minimo, normalizzazione righe legacy.
- **Wizard**: `PartnerHotelRoomsTest` esteso (aggiungi/modifica/elimina,
  validazione, casa vacanza a stanza unica); `StructureCreateTest` admin.
- **Catalogo**: sezione stanze visibile con ≥ 2 stanze e nascosta con 1;
  selezione stanza → prezzo e limiti stepper; struttura senza stanze invariata.
- **Carrello e checkout**: `room_id` obbligatorio, di un'altra struttura
  rifiutato, oltre capienza rifiutato; partner Online con stanza piena ⇒ errore
  in add-to-cart e in pipeline (storno); partner OnSite ⇒ nessun blocco per
  occupazione; `order_items.room_id` e snapshot del nome.
- **Accorpamento**: dry-run non scrive; merge crea stanze sulla bozza, sposta
  recensioni e ordini, nasconde le sorgenti; vecchio URL → 301.
- Regressione: suite completa (`HolidayPagesTest`, `CatalogSearchTest`,
  `Cart/*`, `Orders/*`, `Favorites/*`).

## Rischi e note

- **Dati demo e seeder** senza stanze: gestiti dal ramo «nessuna stanza».
- **Smartbox** che includono una struttura: continuano a puntare alla
  struttura, senza scelta della stanza (fuori perimetro).
- **Chiusure per stanza**: non previste. Se un partner Online vuole bloccare una
  sola camera dovrà ridurre le `units` o chiudere tutta la struttura: da
  segnalare alla cliente.
- In produzione l'accorpamento del Casale va lanciato dopo il deploy, prima in
  `--dry-run`.
