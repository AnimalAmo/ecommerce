# Risposte della cliente del 27/09/2026 — piano degli interventi

**Fonte:** `animal_amo/risposte-cliente.txt` (Isabella, 27/09/2026), che risponde alle dieci domande di
`docs/segnalazioni-domande-cliente-2026-09-26.md`. Il piano tecnico precedente
(`docs/plans/2026-09-26-segnalazioni-cliente-piano.md`) resta valido per le sei richieste originali: questo
documento chiude le sue domande aperte, aggiunge le quattro richieste nuove e rifà l'ordine di esecuzione.

**Branch attuale:** `feature/activity-professional-categories`, 5 commit sopra `main` (`b868fa6`), **non
pushati**. Dato e opzioni delle categorie professionali già fatti e verdi; restano wizard, pannello e scheda.

**Baseline test:** zero rossi nella VM Homestead su Linux (`aa-vm-test.sh`), `20 failed` in Docker/WSL per la
regola `email:rfc,dns` senza DNS. Qualunque altro numero è nostro.

---

## 0. Cosa ha deciso la cliente, e cosa costa

| # | Domanda | Risposta | Effetto sul lavoro |
|---|---|---|---|
| 1 | Tipologie di attività | 8 voci, **scelta multipla** | già in corso, era il ramo costoso |
| 2 | Tipologie di evento | 9 voci, **multipla se non complica** | complica poco: il pattern JSON è già scritto per le attività → **si fa multipla** |
| 3 | Data per le attività | facoltativa; al suo posto **zona in cui opera** + **orari** (facoltativi) | gate `isPublishable()` da dividere per `type`, due campi nuovi |
| 4 | Singolo / ricorrente | **etichetta**, niente generazione di date | una colonna, non il bivio da 6–8 giorni |
| 5 | Posti e prenotazione | posti = **numero che blocca**; prenotazione = informazione; luogo **obbligatorio** | `EventPublisher` da sbloccare, CTA "Partecipa" da rivedere |
| 6 | Contatti | **(a)** recapiti pubblici nuovi con consenso; **nascosti quando il partner incassa online**; indirizzo sempre visibile | 6 campi + consenso + visibilità condizionata |
| 7 | Checkout in struttura | **tenuto, spento** | zero sviluppo, solo test di non-regressione |
| 8 | Carrelli vecchi | **non svuotare**: bloccare al pagamento con la spiegazione e i contatti | guardia in checkout, non in `CartManager` |
| 9 | Smartbox | **non pubblicabile** senza pagamento online, messaggio in dashboard, fuori dalla vetrina | gate nuovo, più stretto di `canPublish()` |
| 10 | Card "Servizi" | resta, rinominata **"Servizio professionale"**, stesso percorso tecnico; **"Evento" come quarta voce già in registrazione** | registrazione a 4 voci + persistenza della scelta |
| extra | 7 servizi non selezionabili | **renderli selezionabili**, "Supplemento animali" compreso | e con loro le 5 orfane inverse |
| extra | Eventi gratuiti | **partecipazione reale** salvata, in profilo, contata, confermata | il pezzo più grosso dei nuovi |

**Totale: 18–23 giorni-uomo**, test e verifica visiva inclusi (era 15,5–21: la revisione di WP9 vale 1,5 g,
vedi il riquadro nel suo pacchetto). Il piano del 26/09 ne stimava ~10 per i punti
4, 5 e i residui: le quattro richieste nuove (eventi gratuiti, amenity, smartbox, carrelli) aggiungono 5,5–8 g,
e la scelta multipla sugli eventi mezza giornata.

---

## 1. Le tre scelte che non erano nelle risposte

Non sono nelle risposte della cliente e cambiano il costo. **Decise con Matteo il 27/09/2026**, le prime due
confermando la raccomandazione.

1. **I recapiti pubblici sono del partner, non della singola struttura** — *deciso*. La cliente scrive «recapiti pubblici
   appositi che *il partner* può scegliere di compilare». Per-partner = una migrazione su `partner_profiles` e
   una sezione di profilo; per-struttura = colonne su tre tabelle, uno step in più su tre wizard, quattro
   publisher (**+3–4 giorni**).
2. **Gli orari di apertura sono un campo solo** — *ipotesi, da confermare con la cliente*: dentro i recapiti pubblici del punto 6, e la scheda di
   un'attività li mostra al posto di "Data inizio / Data fine" (punto 3). La cliente li nomina due volte;
   tenerli in due posti significherebbe due campi che possono contraddirsi.
3. **Per partecipare a un evento gratuito serve il login.** È la stessa regola già scelta per la prenotazione
   con pagamento in struttura: senza un utente la partecipazione non è attribuibile a nessuno e non può
   comparire in "Eventi a cui partecipo".

---

## 2. La decisione tecnica che vale la pena spiegare: gli eventi gratuiti

La richiesta è: partecipazione salvata, evento in "Eventi a cui partecipo", elenco iscritti per il partner,
posti scalati, conferma all'utente. Due strade.

**(A) Ordine da 0 €.** `ProfileEvents::bookedEvents()` (`app/Livewire/Profile/ProfileEvents.php:54`) legge
`order_items`, non una tabella di partecipazioni. La dashboard prenotazioni del partner legge gli ordini.
`ReserveAvailabilityPipe` (`app/Pipes/Order/ReserveAvailabilityPipe.php:66`) verifica la capienza e incrementa
`booked_participants`. Il ramo "prenota e paga in struttura" sa già creare un ordine `confirmed` **senza
gateway** — ed è proprio il ramo che la cliente ha chiesto di tenere spento ma non cancellato (punto 7).
Una partecipazione gratuita è quel ramo con totale zero: cinque requisiti su cinque arrivano dall'esistente.

**(B) Tabella `event_participants`.** Concetto nuovo parallelo agli ordini: una pagina profilo nuova o un
secondo ramo in quella esistente, una schermata partner nuova, un incremento di `booked_participants` scritto
a mano (con il `lockForUpdate` da rifare), una mail nuova, e la domanda "cosa vede il partner" da riprogettare.

**Scelta: (A)**, decisa il 27/09/2026, con `total_cents = 0` e una modalità di pagamento dedicata sulla testata dell'ordine.
Due cose da guardare in faccia: gli ordini a zero non devono comparire nei bonifici (`order_payouts` filtra già
per importo, va verificato con un test) e "annullare la partecipazione" diventa la cancellazione di un ordine,
che oggi **non esiste per nessun ordine** — resta fuori perimetro, come già deciso il 22/09 per camere e
inventario.

---

## 3. Pacchetti di lavoro

Partizionati per insiemi di file disgiunti. Dove si sovrappongono lo dico, e vanno in sequenza.

### Tranche A — sbloccare i partner fermi · 2,5–4 g

#### WP0 · Diagnosi in produzione e recupero schede · 0,5 g · **serve accesso al server**

**Runbook pronto: `docs/runbook-wp0-diagnosi-produzione-2026-09-27.md`.** Tre blocchi — i primi due in sola
lettura, il terzo scrive — più la tabella che dice cosa significa ogni risultato. Da questa macchina non c'è
accesso SSH al server Forge (`/home/forge/animalamo.it`): i comandi li esegue chi ce l'ha, e serve l'output
grezzo.

La cliente ha autorizzato: «procedi pure con la verifica sul server e con il recupero delle schede rimaste
bloccate», e **aspetta la conferma prima di dirlo ai partner**. Comandi e query stanno in
`docs/plans/2026-09-26-segnalazioni-cliente-piano.md` §WP0. Quattro cose non deducibili dal codice: cron
`schedule:run`, `queue:work`, `ADMIN_MODERATION`, quanti `whsec_`. Poi `animalamo:stuck-drafts --fix`, e il
controllo caso per caso delle province non riconosciute (FM-6): una scheda può risultare pubblicata e comunque
non comparire su nessuna pagina regione.

#### WP1-resto · Il resto dei fix Stripe · 0,5–1 g

Restano dal 26/09: regola `exists` sulla provincia nei due form del wizard (i form del pannello ce l'hanno già)
più il log quando `region_id` resta NULL; event id, type e header `Stripe-Account` nel log del webhook
rifiutato; guardia sul profilo mancante in `ensureAccountFor()`; `animalamo:connect-sync` che stampi **quali**
profili ha cambiato.

Se ne aggiunge una trovata il 27/09, che riguarda i soldi: **`payouts:retry` non è nello schedule.**

> **Deciso il 27/09: non lo si schedula in questa tranche.** `RetryFailedPayoutsCommand.php:24-30` fa una
> UPDATE che **azzera `payout_attempts`** filtrando solo sullo stato, e `config/commerce.php` ha un tetto di
> cinque tentativi: schedularlo così trasformerebbe cinque tentativi in tentativi infiniti su un conto rotto
> per sempre. In tranche A si rende solo **visibile** il problema — alla fine del rilascio giornaliero un log
> `critical` col numero di righe `Failed`, che oggi non le riprende nessuno. La retry limitata (una colonna di
> conteggio più un tetto) si decide sui numeri di produzione, che il blocco 1 punto 8 del runbook WP0 stampa.
`routes/console.php` pianifica `payouts:reconcile-net` alle 05:45 e `payouts:release` alle 06:00, ma non il
retry. Il comando esiste (`app/Console/Commands/RetryFailedPayoutsCommand.php`) e il commento in
`ReleaseMaturedPayouts.php:156` dà per scontato che qualcuno lo esegua: **una riga di bonifico che esaurisce i
tentativi non la riprende nessuno**, e nessuno se ne accorge. Va schedulato dopo `payouts:release`.

#### WP9 · Smartbox senza pagamento online · ~~0,5–1 g~~ → **2–2,5 g**

> **Revisione di stima del 27/09, a ground truth fatto.** La stima di 0,5–1 g era sbagliata perché guardava
> solo il gate. Il lavoro vero è in quello che il gate trascina: lo stato mostrato al partner (una smartbox
> ritirata, se non si aggiunge uno stato, legge «Sospesa» — accusa l'admin di una cosa che non ha fatto), il
> contatore in dashboard che oggi azzera le smartbox, il pannello admin che altrimenti pubblica ciò che il
> partner non può pubblicare, il cron dei dieci minuti che riproverebbe all'infinito le righe ritirate, e
> **quattro** classi di test che cadono in blocco perché usano la smartbox come famiglia di default dei loro
> helper. Nessuno di questi pezzi è rinunciabile senza lasciare in piedi una bugia visibile al partner.

Oggi il gate è `PartnerProfile::canPublish()` = `!requiresOnlinePayment() || canBePaid()`
(`app/Models/Partner/PartnerProfile.php:81`): un partner offline pubblica tutto, smartbox comprese, e la scheda
mostra i contatti al posto del pulsante. Un cofanetto prepagato non si compra per telefono.

1. Gate per famiglia su `PartnerProfile`: per la famiglia `smartbox` serve `requiresOnlinePayment() && canBePaid()`,
   applicato nei tre percorsi di pubblicazione (wizard, differita, pannello). **Deciso il 27/09** la lettura
   stretta e non il solo `canBePaid()`: un cofanetto prepagato di un partner in modalità "pagamento diretto"
   finirebbe nel percorso "paghi in struttura", che WP10 spegne nella stessa tranche, quindi non sarebbe
   comunque acquistabile — ed è esattamente il «non la terrei in vetrina se non è acquistabile» della cliente.
   La reversibilità non va costruita: `PartnerPaymentModeService::set()` chiama già `publishAwaitingDrafts()` a
   ogni cambio di modalità, e `canSwitchToOnline()` impone che si torni online solo da pagabili.
2. Messaggio in area partner («Per pubblicare e vendere una Smartbox è necessario collegare il sistema di
   pagamento»), nello stesso punto dove `DraftPublicationState` già distingue le cause.
3. Le smartbox **già pubblicate** di partner offline vanno ritirate dalla vetrina: migrazione dati, censita
   prima con una query, perché tocca righe vive in produzione.

#### WP10 · Carrelli vecchi e pulsanti nelle liste · 1–1,5 g

La cliente ha scelto: **non svuotare**, bloccare al pagamento. Quindi la guardia **non** va in
`CartManager::addItem()` (`app/Services/Cart/CartManager.php:46`) — quella versione l'avevo scritta e toglieva
40 test del checkout in struttura — ma nel passaggio al pagamento, accanto a `guardGiftIsPaidOnline`
(`:168`), con il messaggio e i contatti del partner.

Nelle liste (pagina Eventi, pagina regione, card preferiti) il pulsante "Aggiungi al carrello" compare ancora
per i partner offline: una lettura delle modalità **per pagina** — non per riquadro — e il pulsante diventa
"Contatta la struttura".

### Tranche B — wizard e dati · 7,5–9 g · **in sequenza**: gli stessi Form object e lo stesso contatore di step

#### WP4b · Quattro voci in registrazione e scelta che sopravvive · 1–1,5 g

Oggi la registrazione ha tre voci (`PartnerRegisterStep2.php:54`: `in:struttura,attivita,servizi`) e il funnel
quattro (`CreateService.php:44`, con `smartbox`). La cliente vuole: **Struttura · Attività · Servizio
professionale · Evento**.

1. "Evento" come quarta voce in registrazione e nel funnel: `service_category = 'attivita'`, `type = 'eventi'`,
   salta lo step della scelta Attività/Evento come già fa "Servizi" per le attività.
2. "Servizi" → **"Servizio professionale"**: solo label e descrizione, `service_category` resta `attivita`.
   **Non toccare `StructureDraft::family()`** (`app/Models/Structure/StructureDraft.php:179`): le bozze
   storiche con `service_category='servizi'` sono state compilate col wizard hotel e sono a catalogo come
   `Structure`; rimapparle le trasformerebbe in `Event` alla prima ri-pubblicazione.
3. La scelta fatta in registrazione va portata fino alla prima scheda: oggi `createAccount()` la dimentica e
   atterra in dashboard. È la mezza giornata che la cliente ha approvato esplicitamente.
4. La quinta card "Smartbox" resta nel funnel e **non** entra in registrazione: non è un tipo di partner.

#### WP5b · Categorie professionali: wizard, pannello, scheda · 1–1,5 g

Dato e opzioni sono fatti (`ServiceOptionLabels.php:96`, colonne JSON su `structure_drafts` **e** `events`).
Restano i tre pezzi già mappati, con le quattro trappole già pagate: il campo va dentro lo step
**ActivityName** (chi entra da "Servizio professionale" salta lo step 1, e `ActivityName` è l'unico componente
che entrambe le strade attraversano); la regola va su `activity_categories.*`; un attributo tradotto spatie
legge `''` e non `null`; il campo libero di "Altro" si rivela con `wire:model.live`.
Poi `Admin\Catalog\ActivityCreate` — se le regole divergono, un'attività creata dall'admin non è più
risalvabile dal partner — e la scheda pubblica.

#### WP5c · Campi delle attività professionali · 2–2,5 g

1. **Punto d'incontro e orari via** dal ramo attività: `ActivityLocationForm.php:31` pretende `meetingPoint.it`
   per tutti. Diventa obbligatorio solo per gli eventi.
2. **Zona in cui opera**: colonna nuova tradotta, non il riuso di `meeting_point`. Sulla scheda è una riga
   diversa e `EventPublisher::venue()` stamperebbe «Ritrovo: *zona*».
3. **Data facoltativa**: `DraftPublisher::isPublishable()` (`:86`) pretende `date_start` per tutta la famiglia
   `attivita`, che contiene anche gli eventi. Va divisa per `type`: `eventi` → data obbligatoria, `attivita` →
   basta il nome, come oggi per le strutture.
4. **Prenotazione** ("possibilità di prenotazione") nello step **Costo**, che è già quello delle condizioni
   commerciali: così non si rinumerano gli step.
5. Sulla scheda pubblica, al posto di "Data inizio / Data fine": zona e orari.

> La rinumerazione degli step è la trappola da non ripagare: un solo step nuovo sposta tutti gli indici
> `saveStep`, 20 stringhe "Step N di 10" (una per step, non condivisa) e la soglia `looksFinished()` di
> `animalamo:stuck-drafts`, che è `current_step >= finalStep() - 1`: dopo la rinumerazione una bozza
> abbandonata alle foto la soddisfa, e `--fix` inizierebbe a pubblicarla.

#### WP6 · Campi degli eventi · 3–3,5 g

1. **Tipologie di evento**, gruppo `event_type` in `ServiceOptionLabels`, nove voci più "Altro" col campo
   libero, **scelta multipla** come le attività: JSON su `structure_drafts` **e** su `events` — senza la
   colonna gemella il valore resta nella bozza e non arriva mai al B2C. Uno slug per voce, non due, anche
   dove la voce accorpa sinonimi.
2. **Singolo / ricorrente**: una colonna, etichetta sulla scheda. Nessuna generazione di date.
3. **Prenotazione obbligatoria / facoltativa**: colonna e riga sulla scheda, nessun effetto sul funnel.
4. **Posti limitati**: `events.max_participants` esiste ed è già usata da `AvailabilityService`, ma
   `EventPublisher` la azzera di proposito (`EventPublisher.php:42`, «capienza illimitata»). Riattivarla è una
   riga e **cambia il comportamento del sito**: gli eventi possono esaurirsi. Da verificare insieme il
   messaggio in checkout e la CTA `hasJoinCta()` su un evento gratuito a posti esauriti — che con WP11
   diventa il caso normale, non un caso limite.
5. **Luogo obbligatorio** per gli eventi: è lo stato attuale, va solo bloccato con un test.

### Tranche C — contatti, servizi, partecipazioni · 5,5–8 g

#### WP3b · Contatti pubblici · 2–3 g

`PartnerContacts` esiste già (`app/Services/Partner/PartnerContacts.php`) e oggi espone ragione sociale,
indirizzo e `payment_url` come "sito". Nessuna delle sei voci chieste esiste a database.

1. Sei colonne su `partner_profiles` — telefono, WhatsApp, email pubblica, sito, indirizzo, orari — più il
   **consenso alla pubblicazione**, che è un dato a sé e va registrato con la data.
2. Sezione nel profilo partner, tutti i campi facoltativi.
3. **Visibilità condizionata**, la parte non ovvia: i recapiti che permettono di scavalcare la piattaforma
   (telefono, WhatsApp, email, sito) si mostrano **solo** quando il partner non incassa online.
   L'**indirizzo resta sempre visibile**, gli orari con lui. La regola sta in un posto solo,
   `PartnerContacts::forPurchasable()`, che le cinque schede già chiamano.
4. Il giorno in cui un partner offline collega Stripe, i contatti si nascondono da soli. Va detto a lui, non
   scoperto da noi: una riga nella sezione profilo.

#### WP8 · I sette servizi non selezionabili (e le cinque orfane inverse) · 1–1,5 g

Il buco è in due direzioni e conviene chiuderlo in un colpo.

- **Non selezionabili** (le sette della cliente): Lavanderia, Ascensore, Noleggio bici, Dog sitter, Dog Beach
  nelle vicinanze, Supplemento animali, Piscina per cani. Nessuno slug del wizard le raggiunge —
  `AMENITY_MAP` ha otto righe per quattordici amenity (`FamilyPublisher.php:26`).
- **Selezionabili ma invisibili**: `tv`, `riscaldamento`, `ricarica_elettrica`, `piscina`, `area_animali` sono
  spuntabili nel wizard e **non hanno riga a catalogo**, quindi non compaiono mai sulla scheda. Il partner le
  indica e non le vede: è lo stesso difetto visto dall'altro lato.

Lavoro: slug nuovi nei gruppi `services` e `animal_services`, righe nuove in `AmenitySeeder`, `AMENITY_MAP`
completata, label `it`/`en`. Attenzione a `'piscina'` (per persone) contro `'Piscina per cani'`: sono due voci,
non una, e oggi `'sauna'` è già mappata su `'Spa'` per approssimazione. Il seeder usa `updateOrCreate`, quindi
è ripetibile; la posizione nel pivot la ricalcola `syncAmenities` a ogni pubblicazione.

Nessuna scheda pubblicata cambia da sola: le amenity si risincronizzano quando il partner ri-salva. Va detto
alla cliente, o si aggiunge un comando di risincronizzazione (non stimato qui).

#### WP11 · Partecipazione reale agli eventi gratuiti · 2,5–3,5 g

Oggi `joinEvent()` apre un pop-up e basta: `EventDetail.php:75-83` e `ActivityDetail.php:99` contengono
`// TODO: partecipazione reale`.

Strada (A) del §2, ordine da 0 €:

1. Login obbligatorio sul "Partecipa" (ipotesi 3), con il ritorno alla scheda dopo l'accesso.
2. Ordine a zero creato dal click, senza passare dal carrello: una partecipazione non è un acquisto e non deve
   entrare nella guardia del partner unico.
3. `ReserveAvailabilityPipe` scala i posti e rispetta `max_participants`: con WP6 attivo un evento gratuito
   può esaurirsi, e la CTA deve dirlo.
4. L'evento compare in "Eventi a cui partecipo" senza scrivere una riga: `ProfileEvents` legge già
   `order_items`.
5. Mail di conferma, sullo stampo di quella della prenotazione in struttura.
6. Elenco iscritti al partner: è la dashboard prenotazioni che già legge gli ordini. Da verificare che gli
   ordini a zero non finiscano nei bonifici.
7. Doppia partecipazione: un utente non deve potersi iscrivere due volte allo stesso evento. È una regola
   nuova, non c'è nulla di simile per gli ordini.

**Fuori perimetro:** annullare la partecipazione. Oggi la cancellazione non esiste per nessun ordine.

#### WP12 · Checkout in struttura spento · 0–0,5 g

La cliente lo tiene. Nessun percorso dell'interfaccia ci porta più: resta da bloccare lo stato con un test di
non-regressione, così il ramo non si riaccende per sbaglio, e da scrivere in `CLAUDE.md` che è spento per
scelta e non morto.

---

## 4. Ordine di esecuzione

```
WP0 diagnosi ─────────────────────────────────────►  (subito, serve il server; la cliente aspetta la conferma)

A:  WP1-resto ──► WP9 smartbox ──► WP10 carrelli e liste            5–6 g  (rivisto dal 2,5–4 iniziale)
B:  WP4b registrazione ──► WP5b categorie ──► WP5c attività ──► WP6 eventi   7,5–9 g
C:  WP3b contatti ──► WP8 amenity ──► WP11 eventi gratuiti          5,5–8 g
```

La tranche B è una catena: gli stessi Form object, lo stesso contatore di step, lo stesso pannello admin.
La C si può lavorare in parallelo alla B da un'altra mano, tranne WP8, che tocca `ServiceOptionLabels` come
WP5b e WP6 e va quindi dopo la B. WP11 dipende da WP6 per i posti limitati: se si vuole anticipare, va fatto
senza il blocco della capienza e ripreso dopo.

**Rilascio consigliato in tre consegne**, nell'ordine delle tranche. La A si può consegnare da sola e sblocca
partner veri; la B è la parte che la cliente sta aspettando per far entrare i professionisti; la C è quella che
tocca le schede già online.

---

## 5. Regole operative

Invariate rispetto al piano del 26/09: test nella VM Homestead (`aa-vm-test.sh`), stile con Pint dentro la VM
(l'host ha PHP 7.4), testi UI sempre via `__()` con la gemella in `lang/en` per i 26 file della whitelist di
`LangParityTest`, migrazioni con classe anonima e `down()` sempre scritto, rotte nuove del wizard con due slug
localizzati e `route:clear` dopo il deploy, Conventional Commits in inglese senza trailer di attribuzione.

Una regola in più, che questo giro rende decisiva: **ogni colonna nuova della bozza vuole la gemella sulla
tabella di catalogo** (`events`, `structures`, `smartbox_packages`) e la mappatura nel publisher. Senza,
il dato resta nella bozza e il B2C non lo vede mai — è già successo con le categorie professionali.
