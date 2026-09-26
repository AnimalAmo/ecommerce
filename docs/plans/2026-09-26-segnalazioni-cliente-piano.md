# Segnalazioni cliente — piano di lavorazione

**Fonte:** `D:/Code/algomera/animal_amo/segnalazione-29-09-2026.txt` (mail di Isabella, 6 richieste).
**Branch:** `feature/segnalazioni-2026-09-29`.
**Baseline test al 26/09/2026:** `20 failed, 1998 passed` in Docker (8507 asserzioni, 5m42s); `20 failed, 1 skipped, 1997 passed` con il PHP nativo di WSL (8502 asserzioni, 6m34s). L'unico test saltato nativamente gira e passa in container. I 20 rossi sono preesistenti e concentrati in tre classi — `PhoneInputTest`, `BecomePartnerFromAccountTest`, `WorkWithUsFlowTest` — tutti causati dalla regola `email:rfc,dns` sulle candidature partner, che senza DNS raggiungibile rifiuta l'email e fa cadere i test a valle su `ModelNotFoundException`. **Non sono regressioni**: qualunque numero diverso da 20 dopo una modifica va indagato.

Metodo: cinque agenti hanno mappato il codice in parallelo, ognuno su un'area, leggendo i file e citando `file:riga`. Quello che segue è la sintesi. Le stime sono in giorni-uomo di sviluppo, test inclusi, verifica visiva inclusa.

---

## 0. Il quadro in una pagina

| # | Richiesta | Stato del codice oggi | Stima |
|---|---|---|---|
| R1 | Niente checkout per chi non ha il pagamento online | La modalità esiste già e funziona, ma **non toglie nulla dal funnel**: aggiunge solo una dicitura. Manca la guardia nel carrello. I **contatti da mostrare non esistono a database**. | 3–4 g (per-partner) / 6–8 g (per-struttura) |
| R2 | Solo le caratteristiche selezionate, via le X rosse | Una riga di filtro in `HasAmenities` + guardie sui box vuoti. **Nessuna informazione voluta va persa**: il `false` non è mai scelto da nessuno, lo genera il publisher. | 0,5–1 g |
| R3 | Percorso di registrazione differenziato | La colonna `type` è **condivisa** fra i due wizard e nessuno la ripulisce al cambio ramo. La card "Servizi" porta al percorso Hotel. | 1,5–2 g |
| R4 | Campi delle attività professionali | Via punto d'incontro e orari: fattibile. Ma **tipologia, contatti e prenotazione non esistono**, e oggi **senza una data l'attività non è pubblicabile**. | 3–4 g |
| R5 | Campi degli eventi | Mancano tipologia evento, ricorrenza, prenotazione obbligatoria/facoltativa. `max_participants` esiste ma il publisher **lo azzera di proposito**. | 2,5–3 g (se "ricorrente" è un'etichetta) |
| R6 | Stripe e pubblicazione | Il blocker di settembre è **chiuso**. Ma sono emerse **10 modalità di guasto**, la prima delle quali spiega esattamente il reclamo. | 1–2 g + diagnosi |

**Totale realistico: 13–17 giorni-uomo**, a patto che la ricorrenza degli eventi resti un'etichetta e i contatti siano del partner e non della singola struttura. Le due varianti "pesanti" aggiungono rispettivamente 6–8 e 3–4 giorni.

---

## 1. R6 — perché una scheda con Stripe collegato non va online

Questa va per prima: ci sono partner bloccati **adesso**, e la diagnosi non costa sviluppo.

Il blocker dell'audit del 14/09 (`docs/2026-09-14-audit-connect-pre-live.md`) — «`account.updated` è l'unica sorgente dei flag Connect» — **è stato chiuso**: `syncAccountState()` ha oggi tre chiamanti (`StripeGateway::syncConnectedAccount()`, `PartnerProfilePayment::mount()`, `animalamo:connect-sync`). Anche la sotto-richiesta «chi non collega Stripe deve poter pubblicare» **è già implementata**: `PartnerProfile::canPublish() = !requiresOnlinePayment() || canBePaid()`.

Il problema è altrove. In ordine di probabilità:

### FM-1 — Le bozze chiuse prima del 22/09/2026 non hanno il segnale (altissima)

La colonna `structure_drafts.publish_requested_at` nasce con la migrazione `2026_09_22_100003` e **non c'è alcun backfill**. Prima di quella data, un partner non ancora pagabile che chiudeva il wizard subiva il rollback della transazione e nessun segnale veniva scritto. Oggi quella bozza:

- non è vista da `scopeAwaitingPublication()` → il comando dei 10 minuti non la pubblicherà mai;
- non è vista da `scopeListableFor()` → **non compare nemmeno in "I miei servizi"**;
- non conta in `Dashboard::awaitingCount()` → nessun banner.

Il partner ha compilato 11 step, ha collegato Stripe, e il servizio non esiste da nessuna parte. È la descrizione letterale del reclamo.

### FM-2 — Partner già pagabile con bozze in attesa (alta)

`StripeConnectService::publishAwaitingDraftsOnPayable()` esce subito se i flag Stripe **non sono cambiati**. Un partner i cui flag erano già `true` non fa scattare nulla, per sempre. Lo copre solo il comando schedulato ogni 10 minuti — **che dipende dal cron `schedule:run`, non verificabile da qui**.

### FM-3 — Il badge mente (alta, è il sintomo puro)

`my-services/index.blade.php:44` mostra «In attesa del collegamento Stripe» su **ogni** bozza con `publish_requested_at` valorizzato, senza guardare lo stato Stripe. Se la bozza è ferma per un altro motivo, il partner legge una diagnosi falsa e apre esattamente il ticket che abbiamo ricevuto.

### FM-5 — `ADMIN_MODERATION` acceso (media, dipende dalla produzione)

Con la moderazione accesa ogni riga nuova nasce `approval_status = 'pending'` e `CatalogVisibleScope` la esclude da liste e dettagli. In area partner **non esiste alcun badge "in attesa di approvazione"**: il partner vede "pubblicato" e il sito non la mostra. **Corollario grave: lo stato `pending` è appiccicoso** — spegnere `ADMIN_MODERATION` non libera le righe già in attesa, perché `approved` è un default *di colonna*, applicato solo all'INSERT.

### FM-6 — `region_id` NULL (media)

`StructurePublisher` deriva la regione da `Province::where('short_name', $draft->province)`, senza fallback e senza log. I form del wizard **non hanno** la regola `exists` (quelli del pannello sì). Con `region_id` NULL la struttura è pubblicata, approvata e **irraggiungibile**: non compare su nessuna pagina regione.

### FM-7 — Un solo `whsec_` (media)

Il codice gestisce correttamente due segreti separati da virgola, ma il `.env` di questo repo **ne contiene uno solo**. Con Connect servono due endpoint: se ne manca uno, ogni `account.updated` fallisce la firma, Stripe ritenta e poi disabilita l'endpoint — che è lo stesso da cui passano i `payment_intent.*`. Aggravante: il log del rifiuto non registra né event id né type né header `Stripe-Account`, quindi con due endpoint sullo stesso URL è impossibile capire quale sta fallendo.

### Le altre

- **FM-4** — bozza in attesa diventata non pubblicabile: ritentata ogni 10 minuti all'infinito, un warning al giorno.
- **FM-8** — la rotta `/webhooks/stripe` dipende da una riga di `payment_gateways` e da una cache; se manca, 404 muto.
- **FM-9** — `ensureAccountFor()` su un partner senza riga `partner_profiles`: crea l'account su Stripe e poi esplode, lasciando account orfani a ogni click.
- **FM-10** — il gate sull'incasso in checkout usa ancora `canSell()` (solo `charges_enabled`) mentre pubblicazione e payout usano `canBePaid()`: nella finestra charges-sì/payouts-no il cliente paga e il bonifico non parte. **È una parola sola, ed era già nell'audit.**

### Cosa serve dalla produzione (non deducibile dal codice)

1. `ADMIN_MODERATION` — decide se esiste un secondo cancello.
2. `QUEUE_CONNECTION` e se gira un `queue:work`.
3. Se il cron `* * * * * php artisan schedule:run` è installato. Senza, cadono insieme la rete di sicurezza della pubblicazione **e** `payouts:release`.
4. Quanti `whsec_` ci sono in `STRIPE_WEBHOOK_SECRET` e lo stato di consegna dei due endpoint nella dashboard Stripe.

---

## 2. Pacchetti di lavoro

Partizionati per **insieme di file disgiunti**, così due pacchetti si possono lavorare in parallelo senza conflitti. Dove i file si sovrappongono lo dico esplicitamente e i pacchetti vanno in sequenza.

### WP0 — Diagnosi in produzione · 0 g di sviluppo · **subito, in parallelo a tutto**

Nessun codice. Da eseguire sul server:

```bash
php artisan animalamo:publish-awaiting-drafts   # se stampa N>0, il cron non gira
php artisan tinker --execute="echo (int) config('admin.moderation');"
php artisan tinker --execute="echo count(explode(',', (string) config('payment.stripe.webhook_secret')));"
php artisan route:list --path=webhooks
```

Query:

```sql
-- FM-1: bozze arrivate in fondo al wizard e mai finite a catalogo
SELECT sd.id, sd.user_id, sd.status, sd.current_step, sd.publish_requested_at, sd.updated_at
FROM structure_drafts sd
LEFT JOIN structures s ON s.structure_draft_id = sd.id
LEFT JOIN events e ON e.structure_draft_id = sd.id
LEFT JOIN smartbox_packages b ON b.structure_draft_id = sd.id
WHERE sd.publish_requested_at IS NULL AND s.id IS NULL AND e.id IS NULL AND b.id IS NULL
  AND sd.current_step >= 9 AND sd.user_id IS NOT NULL;

-- FM-5: righe pubblicate ma invisibili
SELECT 'structures' t, approval_status, COUNT(*) FROM structures GROUP BY approval_status
UNION ALL SELECT 'events', approval_status, COUNT(*) FROM events GROUP BY approval_status
UNION ALL SELECT 'smartbox_packages', approval_status, COUNT(*) FROM smartbox_packages GROUP BY approval_status;

-- FM-6: strutture senza regione
SELECT id, slug, user_id, structure_draft_id FROM structures
WHERE region_id IS NULL AND structure_draft_id IS NOT NULL;
SELECT DISTINCT province FROM structure_drafts
WHERE province IS NOT NULL AND province NOT IN (SELECT short_name FROM provinces);
```

**Il risultato di WP0 decide la priorità di WP1.** Senza questi numeri si lavora al buio.

### WP7 — Strumenti · ✅ **fatto** (26/09)

Ambiente di sviluppo ricostruito: **Laravel Sail (PHP 8.3) dentro WSL**, sul modello di oh-my-gloria.

- Il repo di lavoro resta su `D:`; una copia vive sul filesystem ext4 di WSL (`~/aa`) e **Docker bind-monta quella**. Mai `/mnt/d` in un container: attraversare il filesystem di Windows rende la suite inutilizzabile.
- `compose.yaml` (Sail 8.3 + MySQL 8.4), `docker/php/no-swoole.ini` e `docker/t.sh` sono versionati. `laravel/sail` aggiunto in require-dev **senza toccare nient'altro**: il primo tentativo con `composer require -W` aveva tirato dentro framework 13.30→13.33 e `brick/math` 0.18→1.0, ed è stato annullato. Nel lock ci sono solo `laravel/sail` e `symfony/yaml`.
- **Trappola trovata e chiusa: l'immagine Sail carica `swoole`, che manda `php artisan test` in segfault.** Exit 255, nessun messaggio, output troncato a metà suite — il primo test a cadere è quello con `withoutDefer()` + `Mail::fake()` in `AdminAuthTest`. Il file ini di swoole viene mascherato dal mount in `compose.yaml`. Il progetto non usa Octane, quindi non si perde niente.
- Costo di Docker misurato, non stimato: **suite intera 342s in container contro 394s nativi — Docker è più veloce**, grazie alla configurazione opcache dell'immagine Sail. (Su un singolo gruppo pesante come `Admin/Catalog` il rapporto si inverte, 152s contro 114s: è la partenza del container a pesare, non l'esecuzione.)
- `CLAUDE.md` e `.claude/skills/pre-commit-check` riscritti: dicevano che l'host ha PHP 8.2 e prescrivevano una VM Homestead che non esiste più. L'host ha **PHP 7.4**, quindi era rotto anche `vendor/bin/pint`, non solo i test.

Resta da fare in questo pacchetto: **`StructureDraftFactory` manca del tutto**. Oggi almeno 20 file di test costruiscono la bozza a mano con `StructureDraft::create([...])`. R4 e R5 aggiungono campi alla bozza: senza factory ogni campo nuovo va rincorso in venti punti. Va creata con gli stati `structure()`, `activity()`, `event()`, `smartbox()`, `completed()`, `awaitingPublication()`, in **additivo** — i test vecchi restano com'erano.

### WP2 — R2, solo le caratteristiche selezionate · 0,5–1 g · **quick win, per primo**

File: `app/Models/Concerns/HasAmenities.php`, i 5 blade di dettaglio catalogo, `ActivityDetail.php`, `EventDetail.php`, 3 test.

Il punto che rende questa richiesta facile e sicura: **nessuno sceglie mai il `false`.** Non esiste UI, colonna o input che permetta a partner o admin di dichiarare un servizio come non incluso — il `false` è generato da `FamilyPublisher::syncAmenities()`, che scrive una riga per *ogni* amenity del catalogo. Filtrando non si perde nessuna informazione voluta.

1. **Filtro in lettura** in `amenityRows()`: una riga. Rollback in una riga.
2. **Guardie sui box vuoti**: tutti i box hanno `min-h-[250px]`, quindi senza guardia una struttura senza servizi mostrerebbe un riquadro bordato alto 250px col solo titolo. Il repo ha già l'idioma giusto (`@if (filled(...))` su `features` e `description`).
3. **Griglia a due colonne** di attività/eventi: se sopravvive una sola colonna, metà sezione resta vuota. Va filtrata nel componente, non nel blade.
4. **Fase 2 opzionale, da rimandare**: far scrivere al publisher solo le selezionate + pulizia dati. La cancellazione **non è reversibile** — l'informazione «questa struttura NON offre la spa» non è ricostruibile. Raccomandazione: nascondere ora, valutare dopo.

> ⚠️ **Da segnalare alla cliente:** 7 delle 14 amenity del catalogo (Lavanderia, Ascensore, Noleggio bici, Dog sitter, Dog Beach nelle vicinanze, Supplemento animali, Piscina per cani) **non sono selezionabili in nessuno step del percorso partner**. Oggi appaiono sempre con la X rossa; dopo questa modifica non appariranno mai. O si aggiungono al wizard, o si tolgono dal catalogo.

### WP1 — R6, i fix di codice · 1–2 g

File: `app/Services/Payment/StripeConnectService.php`, `app/Http/Controllers/…StripeWebhookController.php`, `app/Livewire/Commerce/Checkout.php` (una riga), `app/Livewire/Forms/HotelLocationForm.php` + `ActivityLocationForm.php`, `resources/views/livewire/partner/my-services/index.blade.php`, una migrazione dati, `app/Console/Commands/ConnectSyncCommand.php`.

1. **Migrazione dati per FM-1**: valorizzare `publish_requested_at` sulle bozze completate prima del 22/09 rimaste senza riga a catalogo. Stampo delle migrazioni-dati già in repo (`2026_07_24_120001`), `down()` vuoto con il commento del perché.
2. **FM-3, il badge**: distinguere «in attesa di Stripe» da «in attesa di approvazione» da «non pubblicabile». Il partner deve leggere la causa vera.
3. **FM-10, una parola**: `canSell()` → `canBePaid()` nel gate del checkout.
4. **FM-6**: regola `exists` sulla provincia nei due form del wizard (i form del pannello ce l'hanno già) + log quando `region_id` resta NULL.
5. **FM-7**: arricchire il log del webhook rifiutato con event id, type e header `Stripe-Account`.
6. **FM-9**: guardia sul profilo mancante in `ensureAccountFor()`, prima di creare l'account su Stripe.
7. **FM-2**: far stampare a `animalamo:connect-sync` **quali** profili ha cambiato.

### WP3 — R1, niente checkout + scheda con i contatti · 3–4 g · **dopo WP2** (stessi 5 blade)

**Prerequisito bloccante: le due domande sui contatti (§3).** Non si scrive una riga prima.

Cosa è già vero e non va toccato:

- **Il "Partecipa" degli eventi gratuiti non passa da lì.** Verificato: `EventDetail::joinEvent()` e `ActivityDetail::joinEvent()` contengono solo `// TODO: partecipazione reale` e aprono un popup. Nessun carrello, nessun ordine. **Rimuovere il checkout in struttura non può rompere le iscrizioni gratuite** — perché oggi quelle iscrizioni non esistono come dato. Va comunque scritto il test di non-regressione.
- **Gli ordini on-site già fatti restano leggibili ovunque**, perché ogni schermata legge `orders.payment_mode` (copia sulla testata), mai la modalità attuale del partner.

Cosa fare:

1. **La guardia centrale è in `CartManager::addItem()`**, non nei blade. Oggi c'è solo `guardGiftIsPaidOnline` per i regali: è il buco che porta al checkout. Senza la guardia server-side, un POST Livewire manomesso riapre il funnel anche a UI pulita.
2. **Sostituire il box prenotazione con la card "Contatti"** sulle schede struttura e servizio (la colonna destra sticky resterebbe altrimenti vuota); su evento e attività la card va collocata in pagina, perché non hanno la colonna sticky.
3. **Disattivare il checkout on-site, non cancellarlo.** R6 dice «quando è realmente previsto un pagamento **o una prenotazione online**»: quel ramo è il solo punto che sa creare un ordine senza gateway, e serve ancora se si vorrà la prenotazione online senza pagamento. Cancellarlo ora e riscriverlo poi è lavoro doppio (~8 file di test).
4. **Saltare il preventivo live** quando il partner è offline: non ha senso quotare ciò che non si compra, ed è una chiamata al pricing in meno per render.

> ⚠️ **I contatti non esistono a database.** Nessuna delle tabelle (`partner_profiles`, `structures`, `events`, `venues`, `structure_drafts`) ha telefono, email pubblica, sito, WhatsApp o orari. L'unico telefono è `users.phone`, cioè un dato di registrazione, e l'unica email è la credenziale di login. Pubblicarli d'ufficio è una scelta che la cliente deve prendere per iscritto. Esiste solo `payment_url` («sito dove pagare o prenotare»), semanticamente diverso.

### WP4 — R3, percorso differenziato · 1,5–2 g

File: `Partner/Structure/StructureType.php`, `Partner/Activity/ActivityType.php`, `Partner/CreateService.php`, `Partner/Registration/PartnerRegisterStep2.php`, `lang/it|en/partner.php`, test del wizard.

Il difetto di fondo: **i due wizard scrivono la stessa colonna `structure_drafts.type`** (uno con `hotel|bb|agriturismo|casa_vacanza`, l'altro con `attivita|eventi`) e **nessuno la ripulisce al cambio ramo**. `ActivityType::mount()` filtra il valore, `StructureType::mount()` no.

1. Guardia in `StructureType::mount()`, identica a quella che `ActivityType` ha già.
2. Pulizia delle colonne dell'altro ramo in entrambi i `next()`, **solo al cambio effettivo**. Il pannello admin ha già questa correzione (`ActivityCreate::updatedType()`), il wizard no.
3. La card "Servizi" del funnel deve portare al wizard Attività. **Non toccare `StructureDraft::family()`**: le bozze storiche con `service_category='servizi'` sono state compilate col wizard hotel e pubblicate come `Structure`; rimapparle le trasformerebbe in `Event` alla prima ri-pubblicazione.
4. Far sopravvivere la scelta fatta in registrazione: oggi `PartnerRegisterStep2::createAccount()` la dimentica e si atterra sempre in dashboard.

### WP5 — R4, attività professionali · 3–4 g · **dopo WP4**

**Prerequisito bloccante: l'elenco delle tipologie e la risposta sulla data (§3).**

1. Nuovo step "Tipologia di attività" con l'idioma "Altro" già in uso nel repo (`activity-included.blade.php:81-93`): `wire:model.live` sul select — **il `.live` è ciò che fa comparire il campo libero senza submit** — e sotto un `locale-tabs` con due textarea. Attenzione alla trappola Flux documentata in `CLAUDE.md`: un campo composto a mano **non emette l'errore di validazione**, servono `<flux:error>` espliciti.
2. Punto d'incontro e orari: rami `@if ($form->isEvent)`, come già fatto per gli orari.
3. **`EventPublisher::venue()` va corretto insieme**: oggi cade sul nome quando il punto d'incontro è vuoto, quindi la scheda B2C stamperebbe «Ritrovo: *nome attività*, *città*». Il `Venue` va conservato (regge la card mappa), ma la riga "Ritrovo" va condizionata.
4. Contatti e "possibilità di prenotazione": colonne nuove. Per evitare un secondo giro di rinumerazione degli step, metterli nello step Costo, già dedicato alle condizioni commerciali.
5. **Rinumerazione**: un solo step nuovo sposta tutti i `saveStep` successivi e 20 chiavi lang "Step N di 10". Raccomandazione: **una sola chiave `partner.wizard.step` con due placeholder** e il totale deciso dal componente — altrimenti, separando davvero i due percorsi, il denominatore diverge fra Attività ed Eventi e una chiave sola non basta più.

> ⚠️ **Blocco funzionale da risolvere prima:** `DraftPublisher::isPublishable()` pretende `date_start` per la famiglia "attivita". **Oggi un dog sitter o un toelettatore, che non hanno una data, non sono pubblicabili.** O si rende la data opzionale per `type='attivita'` cambiando il gate, o il professionista deve inventarsi una data.

### WP6 — R5, eventi · 2,5–3 g (se "ricorrente" è un'etichetta) · **dopo WP5**

1. Tipologia evento + "Altro" (stesso Form object di WP5, con il flag `isEvent`).
2. Ricorrenza e prenotazione obbligatoria/facoltativa: colonne nuove su `structure_drafts` **e** su `events` — senza le seconde i campi restano nella bozza e non arrivano mai al B2C.
3. **Posti limitati**: la colonna `events.max_participants` esiste già ed è già usata da `AvailabilityService`, ma `EventPublisher` la azzera di proposito («Capienza illimitata: nessun input wizard, audit finding 11»). Attivarla è una riga — ma **cambia il comportamento del sito**: gli eventi potranno esaurirsi, e vanno verificati il messaggio in checkout e la CTA `hasJoinCta()` sugli eventi gratuiti a posti limitati.

> ⚠️ **"Evento ricorrente" è un bivio da 6–8 giorni.** Se è un'etichetta descrittiva, è una colonna. Se deve generare date reali, servono più righe `events` per una sola bozza e **si rompe il modello attuale** (`updateOrCreate` su `structure_draft_id`, una riga di catalogo per bozza).

### Allineamento del pannello admin — trasversale a WP5/WP6

`Admin\Catalog\ActivityCreate` comprime i 10 step in una pagina e **condivide i Form object del wizard**. Se le due parti divergono, un servizio creato dall'admin **non è più risalvabile dal partner**. È anche la classe con l'ordine di merge più fragile del repo (c'è un commento esplicito sul `=` vs `??=`): toccarla fuori dall'ordine documentato disattiva silenziosamente le regole dei Form object.

---

## 3. Domande per la cliente

Le prime cinque sono bloccanti: senza risposta, il lavoro parte con un'ipotesi che può costare giorni di rifacimento. Per ognuna indico cosa farei io se non arriva risposta.

**1. I contatti pubblici sono del partner o della singola struttura?**
È il bivio più costoso. Per-partner = una migrazione e una sezione di profilo. Per-struttura = colonne nuove su tre tabelle, un nuovo step replicato sui tre wizard, quattro publisher da toccare.
→ *Default: per-partner.* (+3–4 giorni se la risposta è "per-struttura")

**2. Quali recapiti mostrare, e possiamo pubblicare quelli della registrazione?**
Telefono, WhatsApp, email, sito, indirizzo, orari: **nessuno esiste oggi come campo pubblico**. `users.phone` e `users.email` sono credenziali di accesso e dati di fatturazione.
→ *Default: chiedere recapiti pubblici nuovi, con una casella di consenso esplicita.* Ogni voce in più è una colonna e un campo di form in più.

**3. Un'attività professionale deve ancora dichiarare una data?**
Oggi senza `date_start` non è pubblicabile. Se la data sparisce, l'attività non ha né date né orari: cosa mostra la scheda al posto di "Data inizio / Data fine"?
→ *Default: data opzionale per le attività, gate di pubblicazione allentato di conseguenza.*

**4. Qual è l'elenco definitivo delle tipologie?** Per le attività la mail ne nomina sette più "altri professionisti"; **per gli eventi non ne nomina nessuna**. Serve la lista chiusa: gli slug vanno a database e cambiarli dopo significa migrare i dati. E: **si può scegliere più di una tipologia** (un maneggio che è anche fattoria didattica)? La differenza è strutturale — colonna stringa contro colonna json.
→ *Default: scelta singola, le sette voci della mail + "Altro"; per gli eventi si resta fermi finché non arriva la lista.*

**5. "Evento ricorrente": etichetta o ricorrenza gestita?** Vedi il riquadro in WP6.
→ *Default: etichetta.*

Le altre, meno urgenti ma da chiudere prima di chiudere il lavoro:

6. La card "Servizi" del funnel va **fusa** dentro "Attività ed Eventi"? R4 lo suggerisce, ma "Servizi" compare anche in registrazione ed è già stata usata da partner esistenti.
7. Le bozze già pubblicate come "servizi" (compilate col percorso hotel) vanno migrate? Migrarle significa trasformare righe `structures` in righe `events`: perdono stanze, check-in/check-out e la loro posizione nel catalogo Holiday.
8. **Una smartbox può esistere per un partner senza pagamento online?** È un prodotto prepagato per definizione. Se no, la strada corretta non è mostrare i contatti sulla sua scheda, ma impedirne la pubblicazione.
9. Cosa succede ai **carrelli già pieni** di prodotti di un partner passato al pagamento diretto? Oggi quelle righe arrivano al checkout.
10. Per gli **eventi** il punto d'incontro resta obbligatorio? R5 elenca "luogo" ma non lo nomina.
11. "Prenotazione obbligatoria / facoltativa" **cambia il comportamento** del sito o è solo informativa? Oggi la CTA è derivata dal prezzo.
12. "Posti limitati" è un **numero** o un sì/no? La colonna a valle è un numero e blocca davvero le prenotazioni: un sì/no non basterebbe a pilotarla.
13. Nelle **liste** di catalogo le strutture senza pagamento online devono essere distinguibili (badge "solo su contatto")? Oggi la distinzione esiste solo sulla scheda, per non pagare una query per card.
14. Se una struttura non ha selezionato nessun servizio, il riquadro **sparisce** o resta con una frase tipo "Nessun servizio indicato"? (Raccomando sparisca: è coerente con la richiesta di non trasmettere percezione negativa.)
15. I contatti del professionista vanno mostrati **pubblicamente** sulla scheda? Nel primo caso il cliente può contattare il partner scavalcando la piattaforma — e quindi la commissione.

---

## 4. Un difetto emerso di striscio, da decidere

**Gli eventi gratuiti non registrano nessuna partecipazione.** `joinEvent()` apre un popup di conferma e basta: nessuna riga a database, nessuna tabella `event_participants`. Di conseguenza un evento gratuito **non comparirà mai** in "Eventi a cui partecipo". Non è causato da nessuna delle sei richieste, ma diventa molto più visibile quando la scheda perde il pulsante di acquisto. Da sapere prima che lo segnali la cliente.

---

## 5. Ordine di esecuzione

```
WP0 diagnosi produzione ──────────────────────────────►  (in parallelo, serve la cliente/hosting)
WP7 strumenti ✅ ──►  WP2 amenity ──►  WP1 fix R6 ──►  WP3 no-checkout ──►  WP4 branching ──►  WP5 attività ──►  WP6 eventi
                      0,5-1 g          1-2 g            3-4 g              1,5-2 g            3-4 g             2,5-3 g
```

WP2 va prima di WP3 perché toccano gli stessi cinque blade. WP4 → WP5 → WP6 sono in sequenza perché insistono sugli stessi Form object e sullo stesso contatore di step.

**Rilascio consigliato in due tranche:** WP7 + WP2 + WP1 (ciò che è sicuro e sblocca i partner fermi) subito; il resto quando arrivano le risposte alle prime cinque domande.

## 6. Regole operative per chi implementa

- Test: `wsl -d Ubuntu -e bash -lc '~/t.sh'` (suite intera in Docker), `~/t.sh --filter=NomeTest` (singolo), `~/t.sh --native` (senza container). Baseline: **20 rossi**.
- Stile: `wsl -d Ubuntu -e bash -lc '~/t.sh --pint --dirty'`. **Non** sull'host: PHP 7.4, e Pint ne vuole ≥8.2.
- Testi UI sempre via `__()`. `LangParityTest` confronta **solo le chiavi** (non i valori né i placeholder) su una whitelist fissa di 26 file: ogni chiave nuova in `lang/it` di quei file **deve** avere la gemella in `lang/en`, o la suite diventa rossa. I file `admin-*.php` sono fuori whitelist ed esistono solo in `it/`.
- Migrazioni: classe anonima, docblock in italiano che cita la richiesta e la data, `->after('colonna')` sempre, `down()` sempre scritto (vuoto solo per le migrazioni-dati, con il commento del perché). Per una colonna indicizzata: prima `dropIndex`, poi `dropColumn` — SQLite non toglie una colonna indicizzata.
- Nuove rotte del wizard: due slug localizzati (`lang/it|en/routes.php`) e **`php artisan route:clear` dopo il deploy**, o la rotta nuova dà 404 in produzione.
- Commit: Conventional Commits in inglese, senza trailer di attribuzione.
