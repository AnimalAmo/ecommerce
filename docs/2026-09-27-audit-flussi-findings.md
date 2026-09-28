# Audit end-to-end dei flussi — 27/09/2026

Eseguito dopo l'atterraggio delle tranche A e B, con cinque agenti: tre auditor read-only su tre aree
(percorsi del wizard, ciclo di vita del dato, lato cliente), un verificatore avversariale e un tester.

**29 difetti candidati → 28 confermati, 1 respinto.** Il verificatore ha anche smontato **8 "flussi
dichiarati sani"**: gli auditor citavano righe che non esistono (riga 192 in un file di 171, righe 253-262 in
un file più corto). Quelli valgono quanto un difetto — sono i punti dove nessuno ha guardato davvero.

I test che riproducono i difetti **esistono**, sono ~2500 righe e stanno sul branch
`audit/flussi-2026-09-27`: sono **rossi per costruzione**, e diventano verdi man mano che i difetti si
chiudono. Su `main` non ci sono, perché `main` resta verde.

## Stato al 27/09/2026, sera

| | difetto | stato |
|---|---|---|
| **C1** | bloccante — ordine pagato con zero euro incassati | ✅ **chiuso** (`a90bec9`), test verdi |
| F1 | foto cancellata dal disco mentre il catalogo la punta | ✅ **chiuso** 28/09 (`ae0f646`) |
| W1 | `detailed_description` obbligatoria che non arriva a catalogo | ✅ **chiuso** 28/09 (`d58aca9`), colonna gemella + travaso |
| W2 | «Indietro» orfana la bozza — **è il caso Metina** | ✅ **chiuso** 28/09 (`cd13ce3`) |
| C2 | CTA carrello accesa con meno posti dei 2 ospiti di default | ✅ **chiuso** 28/09 (`d58aca9`) |
| C5 | liste e preferiti ignorano la capienza | ✅ **chiuso** 28/09 (`d34676d`) |
| C6 | borsa e cuore finti sulle card suggerite del carrello | ✅ **chiuso** 28/09 (`d34676d`) |
| C4 | «Partecipa» dichiara un fatto non avvenuto | 🔸 **rimandato per scelta**: si aspetta la partecipazione vera (tranche C) |
| F2, F3 | messaggi al partner sulla causa dell'attesa | ✅ **chiusi** 28/09 (`3d21ea3`) |
| C9 | righe che spariscono dal carrello in silenzio | ✅ **chiuso** 28/09 (`055790a`) |
| C8, F4 | smartbox non ritirate, pannello cieco al ritiro | ✅ **chiusi** 28/09 (`f97c7d2`) |
| C7, C10 | guardie server-side delle schede, checkout di un venditore non pagabile | ✅ **chiusi** 28/09 (`b92bc42`) |
| W5, F6, W4 | traduzioni che non si tolgono, «Altro» che sopravvive, «Indietro» dello step Nome | ✅ **chiusi** 28/09 (`c348f08`) |
| F5, W7 | cambio di ramo, coordinate bancarie solo sulla bozza | ✅ **chiusi** 28/09 (`448e040`) |
| F7, F8 | dettaglio servizio cieco ai campi nuovi, gruppi di stuck-drafts | ✅ **chiusi** 28/09 (`fbdc82d`) |
| W9, F9 | ricerca case-sensitive su MySQL, regole del pannello | ✅ **chiusi** 28/09 (`8f35da0`) |
| W8 | nessun test sulla preselezione dall'iscrizione | ✅ coperto dai test dell'audit, verdi |
| W6 | step «Smartbox» del percorso Struttura inerte | 🔸 **metà**: il partner la rilegge nel dettaglio; collegarla ai cofanetti o togliere lo step è da decidere con la cliente |
| C3 | i posti non si liberano mai | 🔸 **decisione**: non esiste un percorso di annullamento ordini; chi lo scriverà deve liberare i posti nella stessa transazione, e la riga «Cancellazione gratuita» del carrello promette una cosa che oggi non si fa |

## Giro del 28/09/2026: i sei gravi

Branch `fix/audit-flussi-2026-09-28`, nato da `audit/flussi-2026-09-27` (cioè main + i test rossi). Quattro
builder su file disgiunti, un tester, poi una code review a tre lenti con due verificatori avversariali.
Suite VM: **50 rossi, 2257 verdi** contro la baseline di **64 rossi, 2192 verdi**. Zero rossi nuovi: i 14
chiusi sono esattamente i test delle sezioni F1, W1, C2, W2, C5 e C6, e i 50 rimasti sono le sezioni dei
difetti ancora aperti. Il branch non va su `main` finché quei 50 non sono chiusi o spostati.

Il tester ha trovato due difetti nuovi dentro le correzioni, chiusi nello stesso giro:

- **A, sicurezza.** `saved` dello step foto è una proprietà Livewire pubblica: `removeSaved()` la scriveva
  nella bozza **prima** di controllare il path contro la bozza, quindi due chiamate forgiate cancellavano
  un file qualsiasi del disco public; `next()` faceva entrare un path altrui nella bozza e in copertina.
  Ora decide la bozza, e un file che un'altra bozza contiene non si cancella comunque.
- **B.** Le liste usavano la soglia di una persona, ma l'aggiunta rapida di un'attività mette due adulti:
  con un posto libero la borsa c'era e il click veniva rifiutato. Soglia e aggiunta leggono ora
  `Event::quickAddPersons()`; per chi incassa in struttura la soglia resta una persona, come in scheda.

La review ha confermato 3 rilievi e ne ha dati per parziali 7 (tutti minori o medi, tutti applicati): un
test del `saved` forgiato protetto a vuoto dalla seconda guardia, un test di pubblicazione fallita che non
distingueva `afterCommit` da una cancellazione immediata, una migrazione non rilanciabile su MySQL, un hex
al posto del token, commenti rimasti al comportamento di prima.

## Giro del 28/09/2026, fase 2: i medi dietro «ho collegato Stripe ma la scheda non va online»

Quattro builder, un tester, una review a tre lenti con due verificatori. Suite VM: **29 rossi, 2340 verdi**; zero
rossi nuovi, 21 chiusi in questa fase. I 29 sono le sezioni F5–F9, W4–W9, C3, C4 e un buco di copertura del
publisher.

Il tester ha trovato tre difetti nelle correzioni, chiusi: la cancellazione dal pannello toglieva le righe dal
carrello con una DELETE diretta (quindi senza avviso); il dettaglio di «I miei servizi» diceva un'altra causa dalla
lista; la prima versione di F3 lasciava senza nessun banner chi aveva in attesa solo smartbox.

La review ne ha confermati due medi, che erano lo stesso difetto: il ritiro delle smartbox scriveva il segnale di
pubblicazione sulle bozze, e al ritorno online il job avrebbe pubblicato anche una modifica riaperta e lasciata a
metà nel wizard (rimandando in moderazione una scheda già approvata). Ora il ritiro non segna le bozze: il
ritorno lo fa `set()` sulla riga, e la dashboard conta le righe ritirate. Gli altri rilievi applicati: la chiave di
sessione del carrello ospite è rimasta `cart` (con `cart.items` un rollback del codice dava 500 a ogni ospite con un
carrello), l'avviso vive in `cart_notice`; lock dei carrelli in ordine di id; testi admin del ritiro veri anche per
le smartbox ritirate dalla migrazione del 27/09 a partner online senza Stripe.

**Restano aperte, e servono decisioni di prodotto:** le righe *legittime* di un partner passato poi al pagamento
diretto restano in carrello e lo legano a quel venditore; la metà di C10 sul catalogo (`canBePaid()` dove si decide
la CTA); con `DemoUserSeeder` il partner demo è online senza Stripe, quindi su un ambiente col catalogo demo ogni
checkout si ferma allo step 1.

## Giro del 28/09/2026, fase 3: il wizard, il dettaglio, il pannello, la ricerca

Quattro builder, un tester, una review a tre lenti con due verificatori. Suite VM: **6 rossi, 2430 verdi**, zero rossi
nuovi. I 6 rimasti aspettano decisioni, non codice: C4 (tre test, la partecipazione vera agli eventi gratuiti è la
tranche C), C3 (due test, non esiste l'annullamento), W6 (un test, adesione dichiarata verso il catalogo).

Il tester ha trovato cinque difetti nelle correzioni, chiusi: W5 sistemato sulla bozza ma non a catalogo (il publisher
ricopiava solo le lingue piene); lo stesso W5 in nove altri punti (step hotel e smartbox, servizi, servizi per animali,
pannello); F5 lasciava online la riga della famiglia vecchia; il dettaglio mostrava l'IBAN vecchio della bozza; su
MySQL `json_unquote` di un `{"it":null}` dà la stringa 'null' e la ricerca «nu» trovava quelle righe. La review ha
aggiunto una cosa: una riga di un'altra famiglia con **prenotazioni future** non si cancella, si ritira dalla vetrina,
o il partner perdeva di vista prenotazioni già pagate.

La skill `b2b-wizard-flow` prescriveva `array_filter` per i campi tradotti, cioè il difetto W5: ora prescrive
`Translations::replacing()`.

**Il branch è pronto per `main`** a meno dei 6 test in attesa di decisione: o si chiudono, o si spostano fuori dal
branch prima del merge (restano sul branch `audit/flussi-2026-09-27`).

## 28/09/2026, chiusura: WP8 e merge su `main`

**WP8** della tranche C (`c86e73e`): i sette servizi della cliente sono selezionabili e le sei voci che il partner
spuntava senza una riga a catalogo (TV, riscaldamento, ricarica elettrica, piscina, area animali, campo da tennis
della smartbox) arrivano sulla scheda. In produzione: `migrate`, `animalamo:resync-amenities --dry-run`, poi il
comando senza opzioni, poi `view:clear`.

**I sei test delle decisioni aperte** (C3 ×2, C4 ×3, W6 ×1) sono `markTestIncomplete` col motivo: `main` resta verde
e la specifica resta nel codice. Quando la decisione arriva si toglie la riga e il test torna il contratto.
Suite prima del merge: **0 falliti, 6 incompleti, 1 skipped, 2516 verdi** (baseline di stamattina: 64 rossi, 2192
verdi).

## Come riprendere

Le quattro lane dei gravi erano già scritte e partizionate su file disgiunti — foto · scheda attività
(W1+C2) · bozza orfana (W2) · liste e preferiti (C5+C6) — più il tester. Lo script sta in
`~/.claude/projects/.../workflows/scripts/gravi-audit-fix-wf_6c6464a3-de5.js` e si rilancia com'è.

Le chiavi lang dei gravi **sono già scritte** su `main` (`partner.my_services.draft`, `.draft_hint`,
`.resume`) e `cart.sold_out` esisteva già: nessuna lane deve toccare `lang/`.

---

## [bloccante] C1 — Ordine gratis riciclando un PaymentIntent già stornato: $step non è #[Locked] e payloadMatchesSession() passa quando la sessione è nulla

ANCORE: app/Livewire/Commerce/Checkout.php:44 | app/Livewire/Commerce/Checkout.php:66 | app/Livewire/Commerce/Checkout.php:304 | app/Livewire/Commerce/Checkout.php:947 | app/Livewire/Commerce/Checkout.php:949 | app/Services/Payment/StripeGateway.php:142 | app/Services/Payment/StripeGateway.php:154 | app/Actions/Order/PlaceOrderAction.php:53 | app/Livewire/Commerce/Checkout.php:400

REPRO: Ogni anello verificato uno per uno. `public int $step = 1;` a Checkout.php:44 NON ha #[Locked] (l'elenco delle proprietà Locked copre 81, 93, 95, 99, 107, 134, 141 — mai 44), e la suite stessa lo dimostra: CheckoutPaymentTest fa `set('step', 2)` chiamandolo «step manomesso via devtools». `paymentMethod = 'card'` è il default a Checkout.php:66, quindi `isMethodAvailable()` passa su un componente appena montato. `payloadMatchesSession()` a :949 è `$this->paymentIntentId === null || ...` → true su un componente nuovo. `captureFromCheckout()` legge SOLO `status !== 'succeeded'` (StripeGateway.php:142) e `amount_received !== $expected` (:154): nessuna lettura di `amount_refunded` né di `latest_charge.refunded` in tutto il file. `findRegisteredOrder()` (PlaceOrderAction.php:53-64) cerca una riga `order_payments` con quel provider+gateway_session_id: dopo uno storno post-capture (Checkout.php:352/400/418) quella riga NON esiste, perché il rollback l'ha annullata. Repro: 1) far fallire un checkout online post-capture (evento a capienza 1, due clienti: il secondo arriva al capture, ReserveAvailabilityPipe trova i posti finiti, rollback + refund a :400); 2) ricaricare /checkout, rimettere in

FIX: #[Locked] su $step (e verificare che goToStep resti l'unica via), più un terzo anello dentro captureFromCheckout: rifiutare un intent con amount_refunded > 0 o latest_charge.refunded === true. L'espansione `latest_charge.balance_transaction` c'è già, basta chiedere anche `latest_charge`.

## [grave] F1 — Togliere una foto già salvata la cancella dal disco mentre la riga a catalogo la punta ancora

ANCORE: app/Livewire/Concerns/HandlesPhotoUploads.php:47 | app/Livewire/Concerns/HandlesPhotoUploads.php:50 | app/Livewire/Concerns/HandlesPhotoUploads.php:53 | app/Livewire/Concerns/HandlesPhotoUploads.php:63 | app/Models/Concerns/HasCatalogImages.php:30 | app/Services/Partner/Publishing/FamilyPublisher.php:88

REPRO: `removeSaved()` (HandlesPhotoUploads.php:47-54) fa `Storage::disk('public')->delete()` a :50 e `$this->draft()->update(['photos' => ...])` a :53 nello stesso click, fuori dal ciclo saveStep→completeDraft→publisher. `HasCatalogImages::resolveImage()` (:30-38) non controlla l'esistenza del file: con un path che contiene '/' compone comunque lo Storage URL. `FamilyPublisher::coverPhoto()` legge `photos[0]` solo alla ripubblicazione. Repro: attività pubblicata con 4 foto (events.img = 'structure-photos/aaa.jpg'); da «I miei servizi» → modifica → step foto → X sulla prima foto salvata; il file è già cancellato e la scheda pubblica serve un'immagine rotta. In più `count($saved)` scende a 3, quindi `collectPhotos()` (:63) blocca l'avanzamento col minimo: se il partner abbandona, la scheda resta online con l'immagine morta e il file non è recuperabile.

FIX: Rinviare la cancellazione su disco alla pubblicazione (raccogliere i path da eliminare e potarli dopo che il publisher ha riscritto img/hero_img), oppure non toccare il disco e limitarsi a riscrivere `photos`.

## [grave] C2 — Attività: la CTA carrello resta accesa con meno posti liberi dei 2 ospiti di default del widget

ANCORE: app/Livewire/Catalog/ActivityDetail.php:239 | app/Livewire/Catalog/ActivityDetail.php:241 | app/Livewire/Catalog/ActivityDetail.php:55 | app/Services/Availability/AvailabilityService.php:134 | app/Services/Pricing/BookingPricingService.php:116 | resources/views/livewire/catalog/activity-detail.blade.php:298

REPRO: `isSoldOut()` (ActivityDetail.php:239-243) è `booked >= max`, cioè la soglia della persona minima; `mount()` a :55 nasce con `['adulti' => 2, ...]`; `AvailabilityService::ensureEventAvailable()` a :134 rifiuta con `booked + persons > max`, dove `persons` è la somma degli ospiti (BookingPricingService::persons, :111-117). Repro: attività a pagamento con max_participants=10 e booked_participants=9 → isSoldOut() false, la CTA «Aggiungi al carrello» viene disegnata (blade :298), il click dà il toast «Non ci sono abbastanza posti disponibili» e nient'altro. `remainingSeats` esiste SOLO su EventDetail (EventDetail.php:130, event-detail.blade.php:193): la scheda attività non dice quanti posti restano, e gli stepper ospiti non sono clampati sulla capienza residua, quindi il cliente non ha modo di capire che bastava scendere a 1.

FIX: Calcolare isSoldOut() sugli ospiti correnti (o clampare gli stepper sulla capienza residua) e passare remainingSeats anche alla scheda attività, come già fa EventDetail.

## [grave] C4 — Eventi gratuiti: «Partecipa» non registra niente e i posti non si muovono mai, quindi il blocco a posti esauriti non può scattare

ANCORE: app/Livewire/Catalog/EventDetail.php:76 | app/Livewire/Catalog/EventDetail.php:82 | app/Livewire/Catalog/ActivityDetail.php:101 | app/Livewire/Profile/ProfileEvents.php:54 | app/Pipes/Order/ReserveAvailabilityPipe.php:66 | resources/views/livewire/catalog/event-detail.blade.php:327 | app/Models/Event/Event.php:98

REPRO: (a) `joinEvent()` (EventDetail.php:76-85) apre solo `$joinPopupOpen`; il TODO è dichiarato a :82 e il pop-up titola «Aggiunto agli eventi» (blade :327, lang/it/events.php:68). Nessun gate di login. `ProfileEvents::bookedEvents()` (:54-...) legge solo `order_items`, e un evento gratuito non genera mai un ordine → «Eventi a cui partecipo» resta vuoto. Identico su ActivityDetail.php:101-109. (b) L'UNICO punto che incrementa `booked_participants` è ReserveAvailabilityPipe.php:66, raggiungibile solo da una riga di carrello, e `hasJoinCta()` (Event.php:98-101, `is_free || price_cents === null`) fa uscire subito `addToCart()` (EventDetail.php:58). Quindi per un evento gratuito booked_participants resta 0 per sempre: `isSoldOut()` (:152) non diventa mai vero e `remainingSeats` (:130) stampa la capienza piena anche dopo 500 pop-up di conferma. Repro: evento gratuito con max_participants=20, cliccare Partecipa da ospite, poi controllare Profilo → Eventi a cui partecipo (vuoto) e la scheda («Posti: 20»).

FIX: Finché la partecipazione reale non c'è, la copy del pop-up non deve affermare un fatto compiuto. Il sotto-caso (c) del rapporto originale — joinEvent() senza guardia di capienza — NON è raggiungibile: richiede booked_participants valorizzato a mano, cosa che nessun percorso applicativo fa per un gratuito.

## [grave] C5 — Nelle liste e nei preferiti il pulsante carrello ignora la capienza: un evento esaurito lo mostra ancora

ANCORE: resources/views/livewire/catalog/events.blade.php:197 | resources/views/livewire/catalog/events.blade.php:203 | resources/views/livewire/catalog/animal-holiday-region.blade.php:216 | resources/views/livewire/catalog/animal-holiday-region.blade.php:221 | app/Services/FavoriteService.php:238 | resources/views/partials/favorite-card.blade.php:38 | app/Services/Availability/AvailabilityService.php:134

REPRO: I tre rami sono stati letti: events.blade.php:197-203 disegna il pulsante borsa nel ramo `@elseif (! $event->hasJoinCta())` — nessuna condizione sulla capienza; animal-holiday-region.blade.php:216-221 idem; `FavoriteService::canAddToCart()` (:238-248) guarda `hasJoinCta()` e la modalità di incasso del titolare e NON `max_participants`/`booked_participants`, e la borsa di partials/favorite-card.blade.php:38 è gated solo da `can_add_to_cart`. Repro: evento a pagamento con max_participants=10 e booked_participants=10; la scheda di dettaglio si comporta bene (CTA via), ma /eventi, /animal-holiday/{regione} e /preferiti mostrano ancora la borsa; il click dà il toast di AvailabilityService.php:134 e nessuna delle tre liste dice che l'evento è pieno.

FIX: La stessa aritmetica di isSoldOut() va nel contratto delle card di lista e in FavoriteService::canAddToCart, che è nato per questa regola («una borsa che non funziona è peggio di nessuna borsa»).

## [grave] C6 — Carrello vuoto: borsa e cuore delle card «Le attività più amate» sono solo colore, e la card dichiara «in carrello»

ANCORE: app/Livewire/Commerce/Cart.php:93 | app/Livewire/Commerce/Cart.php:103 | resources/views/livewire/commerce/cart.blade.php:95 | resources/views/livewire/commerce/cart.blade.php:96 | resources/views/partials/favorite-card.blade.php:38 | app/Livewire/Commerce/Favorites.php:40

REPRO: `toggleSuggestionFavorite()` (Cart.php:93-101) e `toggleSuggestionCart()` (:103-110) si limitano a infilare/togliere la chiave da `$suggestFavorites`/`$suggestInCart`: nessuna chiamata a CartManager né a FavoriteService. Il blade le passa come `heartAction`/`bagAction` al partial (cart.blade.php:95-96), e favorite-card.blade.php:38 usa `$bagActive` per portare il bottone a #FFE13E e l'aria-label a «Rimuovi dal carrello». Repro: aprire /carrello vuoto, cliccare la borsa su una card «Le attività più amate»: il bottone diventa giallo e dice «Rimuovi dal carrello», il carrello resta vuoto, il contatore in tabbar non si muove, ricaricando lo stato sparisce. Sulla pagina /preferiti la borsa graficamente identica (Favorites::toggleCart, :40-55) aggiunge davvero: lo stesso componente ha due comportamenti opposti.

FIX: Collegare le due azioni a FavoriteService (come Favorites::toggleCart/toggleFavorite) o togliere i due bottoni dalle card suggerite: uno stato visivo che mente è peggio di un bottone assente.

## [grave] W1 — detailed_description: campo OBBLIGATORIO del ramo Attività/Servizio professionale che non ha gemella su `events` e nessun publisher legge

ANCORE: app/Livewire/Partner/Activity/ActivityDescription.php:26 | app/Livewire/Partner/Activity/ActivityDescription.php:36 | app/Livewire/Partner/Activity/ActivityDescription.php:48 | app/Services/Partner/Publishing/EventPublisher.php:71 | app/Livewire/Admin/Catalog/ActivityCreate.php:452 | resources/views/livewire/catalog/activity-detail.blade.php:76 | resources/views/livewire/catalog/activity-detail.blade.php:183

REPRO: `ActivityDescription::mount()` calcola `isActivity` a :26, `next()` rende `detailedDescription.it` `required` a :36 e la salva su `structure_drafts.detailed_description` a :48. L'`updateOrCreate` di EventPublisher (righe 22-76) scrive `'description' => $this->translations($draft, 'description')` a :71 e NON nomina mai `detailed_description`; la colonna non esiste su `events` — le sole migrazioni che toccano quella tabella sono create_events_table, max_participants, booked_participants, activity_categories (26/09), operating_area, event_categories, recurrence+booking_requirement (27/09), nessuna con `detailed_description`. La gemella esiste per la smartbox (SmartboxPublisher.php:39 → `extended_description`) e non per le attività. Repro: entrare dalla card «Servizio professionale», compilare la descrizione dettagliata (senza cui non si avanza), pubblicare, aprire la scheda pubblica: `$activity->description` viene stampata DUE volte (activity-detail.blade.php:73/76 «Descrizione» e 180/183 «Attività») e la dettagliata non c'è da nessuna parte. Identico dal pannello admin, che scrive la stessa colonna di bozza (ActivityCreate.php:452).

FIX: O la colonna gemella su `events` con la riga nel publisher e il posto nella scheda (la seconda sezione «Attività», che oggi ripete la breve), o togliere il required: un campo obbligatorio che nessuno legge è la peggiore delle due.

## [grave] W2 — «Indietro» dal primo step orfana la bozza in corso: il compilato si perde in silenzio e non è più raggiungibile da nessuna schermata partner

ANCORE: app/Livewire/Concerns/InteractsWithStructureDraft.php:86 | app/Livewire/Partner/CreateService.php:44 | app/Livewire/Partner/CreateService.php:48 | app/Livewire/Partner/CreateService.php:51 | app/Livewire/Partner/CreateService.php:87 | app/Models/Structure/StructureDraft.php:151 | resources/views/livewire/partner/activity/activity-type.blade.php:41

REPRO: `serviceChoiceBackUrl()` (InteractsWithStructureDraft.php:86-92) manda su `partner.service.create` ogni bozza che non è COMPLETED e non è in attesa; il pulsante «Indietro» di activity-type.blade.php:41 usa quell'URL. `CreateService::mount()` legge la bozza a :44, e se `current_step > 0` (:48) fa `session()->forget`, azzera `draftId` e ne crea una NUOVA a :51. La vecchia riga resta con nome, categorie e indirizzo, con status `draft` e `publish_requested_at` nullo: `scopeListableFor` (StructureDraft.php:151-158) richiede COMPLETED oppure publish_requested_at, e TUTTI gli ingressi partner passano da lì (PartnerMyServices.php:31 e :50, PartnerServiceDetail.php:20, DeleteServiceModal.php:31 e :61) — nessuna schermata la può riaprire. In più il radio si presenta vuoto: `registrationChoice()` (:87-92) trova già una bozza con `service_category` non nullo (l'orfana) e torna ''. Repro: card «Attività» → step tipo → nome+categorie → Luogo → «Indietro» fino ad activity.type → «Indietro» ancora. Per le card «Servizio professionale» ed «Evento» basta UN «Indietro», perché CreateService::next() salva già `current_step = 1` (:130-133).

FIX: mount() non deve scollegare la bozza dalla sessione senza darle un'altra porta: o riprende la bozza avanzata (con un avviso), o offre un ingresso a una bozza `draft` da «I miei servizi».

## [medio] F4 — Il pannello admin è cieco al ritiro: una smartbox `withheld_at` risulta «Pubblicata», rientra nel filtro «pubblicate» e non entra in nessun contatore

ANCORE: app/Services/Admin/Catalog/CatalogAdmin.php:110 | app/Services/Admin/Catalog/CatalogAdmin.php:116 | app/Services/Admin/Catalog/CatalogAdmin.php:71 | app/Services/Admin/Catalog/CatalogAdmin.php:415 | app/Services/Admin/People/UserDirectory.php:197 | app/Services/Admin/People/UserDirectory.php:198 | app/Services/Admin/Dashboard/DashboardOverview.php:175 | app/Models/Scopes/CatalogVisibleScope.php:35

REPRO: `grep -rn withheld app/Services/Admin app/Livewire/Admin` non restituisce NIENTE. `CatalogAdmin::status()` (:110-118) fa match solo su `approval_status` e `suspended_at` → una riga con `withheld_at` esce `published` col badge verde; il ramo `STATUS_PUBLISHED` del filtro (:415) è `approval_status = approved AND suspended_at IS NULL` → la include; `totals()` (:71-83) la conta in `total` e non in `suspended` né in `pending`; `DashboardOverview::latestListings()` (:170-183) usa `withHidden()` e lo stesso presenter; `UserDirectory::partnerSummary()` (:197-198) la conta in `listings` e non in `suspended`. Nel frattempo `CatalogVisibleScope` (:35) la nasconde al sito e `DraftPublicationState::state()` (:94-96) dice al partner «Serve il sistema di pagamento». Repro: eseguire la migrazione dati 2026_09_27_100002 (o scrivere withheld_at su una smartbox), aprire admin → Catalogo: badge verde «Pubblicata», presente nel filtro «pubblicate», assente da ogni contatore.

FIX: Uno stato amministrativo `withheld` in CatalogAdmin::status(), nel match del filtro, in totals() e in partnerSummary(): lo scope e la diagnostica partner lo conoscono già.

## [medio] F2 — Il messaggio di fine wizard dice «completa il collegamento Stripe» anche alla smartbox di chi incassa in struttura: la causa vera viene buttata da DraftCompleter

ANCORE: app/Services/Partner/Publishing/DraftPublisher.php:56 | app/Services/Partner/Publishing/DraftPublisher.php:57 | app/Services/Partner/Publishing/DraftCompleter.php:62 | app/Enums/DraftCompletion.php:15 | app/Livewire/Concerns/InteractsWithStructureDraft.php:120 | app/Livewire/Concerns/InteractsWithStructureDraft.php:122 | lang/it/partner.php:14 | lang/it/partner.php:19 | app/Livewire/Admin/Catalog/Concerns/CreatesPartnerService.php:234

REPRO: DraftPublisher.php:56-59 distingue la causa e lancia `PartnerNotPayableException::smartboxRequiresOnlinePayment()`, il cui testo (lang/it/partner.php:14) parla di incasso online. `DraftCompleter::complete()` la intercetta con `catch (PartnerNotPayableException)` SENZA variabile (:62) e torna `DraftCompletion::AwaitingPayout`, enum a due soli casi (DraftCompletion.php:12-15). `completeDraft()` (InteractsWithStructureDraft.php:118-124) scegle il flash solo su `$wasCompleted`, quindi flasha `partner.publish.awaiting_stripe` = «lo pubblicheremo in automatico appena completi il collegamento del conto su Stripe» (lang/it/partner.php:19). Repro: partner con `online_payment = false`, wizard smartbox fino a SmartboxPrice::save() → in dashboard legge di collegare Stripe; lo collega e la smartbox NON va in vetrina, perché `canPublishFamily('smartbox')` vuole `requiresOnlinePayment() && canBePaid()` (PartnerProfile.php:124). Il pannello admin lo gestisce bene (CreatesPartnerService::smartboxPaymentBlock).

FIX: Portare la causa fuori dal catch: un terzo caso dell'enum, o il messaggio dell'eccezione nel flash. Il publisher e il pannello la conoscono già, solo il wizard la perde.

## [medio] F6 — Il testo libero di «Altro» sopravvive alla deselezione della casella e finisce pubblicato sulla scheda

ANCORE: app/Livewire/Partner/Activity/ActivityName.php:55 | app/Livewire/Partner/Activity/ActivityName.php:85 | resources/views/livewire/partner/activity/activity-name.blade.php:71 | app/Services/Partner/Publishing/EventPublisher.php:32 | app/Services/Partner/Publishing/EventPublisher.php:37 | resources/views/livewire/catalog/activity-detail.blade.php:97 | resources/views/livewire/catalog/event-detail.blade.php:141 | app/Livewire/Admin/Catalog/ActivityCreate.php:439

REPRO: `mount()` idrata `$categoriesOther` da `getTranslations($this->categoriesOtherColumn())` (ActivityName.php:55-58); il blade disegna il campo SOLO dentro `@if (in_array('altro', $categories, true))` (:71); `next()` scrive `$this->categoriesOtherColumn() => array_filter($this->categoriesOther, ...)` senza guardare se 'altro' è ancora selezionato (:85). EventPublisher copia la colonna (:32 per le attività, :37 per gli eventi) e i due blade la stampano gated solo su `filled(...)`, mai sulla presenza dello slug (activity-detail.blade.php:97, event-detail.blade.php:141). Repro: step nome, spuntare «Altro» e scrivere «Pensione per gatti a domicilio», salvare, riaprire lo step, togliere «Altro» e spuntare «Toelettatore», salvare, pubblicare: la scheda stampa «Tipologia: Toelettatore» con la sotto-riga grigia «Pensione per gatti a domicilio». Dal wizard il testo si può svuotare solo rispuntando «Altro», cancellandolo e ritogliendo la spunta. Identico dal pannello, dove il campo è sempre visibile e scollegato dalla casella (ActivityCreate.php:439).

FIX: -

## [medio] F5 — StructureType::clearedFields() non è stata estesa alle colonne del 26-27/09, mentre ActivityType sì: passando da Eventi a hotel i posti restano e la scheda esce come attività esauribile

ANCORE: app/Livewire/Partner/Structure/StructureType.php:37 | app/Livewire/Partner/Structure/StructureType.php:49 | app/Livewire/Partner/Structure/StructureType.php:57 | app/Livewire/Partner/Activity/ActivityType.php:83 | app/Livewire/Partner/Activity/ActivityType.php:91 | app/Services/Partner/Publishing/EventPublisher.php:48 | app/Services/Partner/Publishing/EventPublisher.php:66 | tests/Feature/Partner/WizardBranchSwitchTest.php:58

REPRO: `StructureType::clearedFields()` (:49-61) azzera sei colonne — `meeting_point, date_start, date_end, time_start, time_end, detailed_description` (:58) — e lascia `activity_categories(_other)`, `operating_area`, `event_categories(_other)`, `recurrence`, `booking_requirement`, `max_participants`. La gemella ActivityType è stata estesa (:83 e :91), questa no. `next()` (:37) riscrive `type` ma NON `service_category`, quindi `family()` resta 'attivita' e la pubblicazione passa ancora da EventPublisher, che copia `booking_requirement` (:48) e `max_participants` (:66) senza guardia sul tipo. Repro: bozza `service_category='attivita'`, `type='eventi'`, `max_participants=30`; navigare per URL a `partner.structure.type` con la bozza in sessione, scegliere 'hotel', completare, pubblicare: esce a catalogo un'Attività con il limite di 30 posti dell'evento abbandonato, quindi esauribile. Reachability: per interfaccia la card «Struttura» da «Crea servizio» orfana la bozza invece di riusarla (è W2), quindi lo scenario dati richiede l'accesso diretto all'URL — resta però la divergenza fra le due liste gemelle, che è il difetto vero. `WizardBranchSwitchTest:58-82` asserisce solo sulle sei colonne ve

FIX: Una sola lista condivisa dai due step (o una regola derivata dal ramo), invece di due elenchi letterali da tenere in pari a mano.

## [medio] W5 — Una traduzione inglese salvata non si può più togliere: array_filter + setTranslations non rimuove i locale assenti, e il publisher la ripubblica per sempre

ANCORE: app/Livewire/Partner/Activity/ActivityName.php:83 | app/Livewire/Partner/Activity/ActivityName.php:85 | app/Livewire/Partner/Activity/ActivityDescription.php:46 | app/Livewire/Partner/Activity/ActivityDescription.php:48 | app/Livewire/Forms/ActivityLocationForm.php:106 | app/Livewire/Forms/ActivityLocationForm.php:108 | app/Livewire/Forms/PartnerProfileForm.php:128 | vendor/spatie/laravel-translatable/src/HasTranslations.php:62 | vendor/spatie/laravel-translatable/src/HasTranslations.php:194 | app/Services/Partner/Publishing/FamilyPublisher.php:68

REPRO: Meccanica verificata nel vendor: `setAttribute` con un array non-lista chiama `setTranslations` (HasTranslations.php:62), e `setTranslations` (:194-206) itera SOLO le chiavi ricevute — nessuna rimozione dei locale assenti. `array_filter(..., filled)` fa cadere la chiave del locale svuotato: ActivityName.php:83 e :85, ActivityDescription.php:46 e :48, ActivityLocationForm.php:106 e :108 (NON 140/142: il file ha 113 righe), PartnerProfileForm.php:128. `FamilyPublisher::translations()` (:68-73) tiene tutti i valori `filled()` e li riporta sulla colonna di catalogo. In tutto il wizard non esiste UNA chiamata a `forgetTranslation()`/`replaceTranslations()`, che il resto del progetto usa invece regolarmente (ArticleService.php:212, ContentBlockService.php:150, FaqService.php:141, PageService.php:156-157, CampaignEdit.php:231). Repro: pubblicare un evento con `name = {"it":"Sagra del cane","en":"Dog Fair"}`; riaprirlo da «I miei servizi», cambiare l'italiano e SVUOTARE il tab EN; salvare e ripubblicare: il visitatore su /en continua a vedere «Dog Fair». L'unica via di uscita (svuotare TUTTE le lingue, così array_filter dà [] e setTranslations scrive '[]') è chiusa dal required sull'italia

FIX: Scrivere l'array completo con i locale a null e usare forgetTranslation/replaceTranslations, come già fa il lato contenuti.

## [medio] C7 — Le cinque schede di dettaglio non hanno la guardia server-side sul pagamento diretto: solo le liste e i preferiti la hanno

ANCORE: app/Livewire/Catalog/AnimalHolidayStructure.php:98 | app/Livewire/Catalog/AnimalHolidayService.php:92 | app/Livewire/Catalog/ActivityDetail.php:70 | app/Livewire/Catalog/EventDetail.php:53 | app/Livewire/Catalog/SmartboxDetail.php:64 | app/Livewire/Concerns/AddsEventToCart.php:40 | app/Services/FavoriteService.php:77 | app/Services/Cart/CartManager.php:153 | app/Services/Cart/CartManager.php:168

REPRO: Letti tutti e cinque i metodi: AnimalHolidayStructure.php:98-111, AnimalHolidayService.php:92-105, ActivityDetail.php:70-92, EventDetail.php:53-74, SmartboxDetail.php:64-86 — nessuno interroga `PartnerPaymentModeService`. `CartManager::addItem` ha solo `guardSinglePartner` (:153-160) e `guardGiftIsPaidOnline` (:168-173). La guardia esiste in due soli posti, entrambi col commento che spiega perché sta lì e non nel CartManager: AddsEventToCart.php:40 e FavoriteService.php:77. Repro: partner con `online_payment=false`; sulla sua scheda la CTA non c'è, ma una chiamata Livewire `addToCart` forgiata mette la riga in carrello. Da quel momento `guardSinglePartner` lega il carrello a quel partner e ogni prodotto acquistabile di un altro venditore viene rifiutato con «un ordine, un venditore» finché il cliente non indovina di dover cancellare la riga fantasma — e lo stesso effetto colpisce senza manomissione chi aveva righe legittime di un partner passato poi al pagamento diretto.

FIX: -

## [medio] C8 — Una smartbox già pubblicata non viene ritirata quando il partner passa al pagamento diretto

ANCORE: app/Services/Partner/PartnerPaymentModeService.php:44 | app/Services/Partner/PartnerPaymentModeService.php:61 | app/Models/Partner/PartnerProfile.php:124 | app/Services/Partner/Publishing/SmartboxPublisher.php:50 | database/migrations/2026_09_27_100002_withhold_smartboxes_without_online_payment.php:35 | resources/views/livewire/catalog/smartbox-detail.blade.php:186

REPRO: `grep -rn withheld_at app database/migrations` dà tre soli punti che SCRIVONO la colonna: la migrazione dati una-tantum, SmartboxPublisher.php:50 (che la azzera) e le definizioni di schema. `PartnerPaymentModeService::set()` (:44-64) salva `online_payment` e chiama solo `publishAwaitingDrafts()` (:155-183): non tocca nessuna riga di catalogo già pubblicata. Repro: partner con un cofanetto a catalogo → Profilo → Dati pagamento → «incasso direttamente». Da quel momento `canPublishFamily('smartbox')` è false (PartnerProfile.php:122-127), quindi una smartbox NUOVA non si pubblica, ma quella vecchia resta in /smartbox e sulla sua scheda, dove al posto del pulsante compare la card contatti «prenota col partner» (smartbox-detail.blade.php:186) — invito che a un cofanetto prepagato da regalare non si applica. E resta aggiungibile al carrello con una chiamata forgiata, per C7.

FIX: Il gemello del ritiro: `set()` che scrive withheld_at sulle smartbox pubblicate quando la modalità passa a offline, esattamente come SmartboxPublisher lo azzera quando torna pubblicabile.

## [medio] C9 — Righe di carrello che escono dal catalogo (ritirate, sospese) o scartate al login sparicono in silenzio, e la riga fantasma resta a database

ANCORE: app/Services/Cart/DatabaseCartStorage.php:127 | app/Services/Cart/SessionCartStorage.php:138 | app/Services/Cart/SessionCartStorage.php:141 | app/Models/CartItem/Concerns/CartItemHasRelationships.php:18 | app/Models/OrderItem/Concerns/OrderItemHasRelationships.php:33 | app/Models/Scopes/CatalogVisibleScope.php:35 | app/Listeners/MergeCartOnLogin.php:45

REPRO: `CartItem::purchasable()` (CartItemHasRelationships.php:17-20) è un `morphTo()` nudo, quindi eredita il global scope: con `withheld_at` o `suspended_at` valorizzati torna null. `DatabaseCartStorage::items()` filtra `purchasable !== null` a :127 e SessionCartStorage :138-142 fa lo stesso, senza nessun messaggio; la riga `cart_items` (o l'entry di sessione) resta a database per sempre, perché ClearCartPipe rimuove solo le chiavi ordinate. Per confronto, `OrderItem::purchasable()` (:33) toglie lo scope di proposito. Repro (a): smartbox in carrello, poi ritirata (withheld_at) o sospesa → il cliente riapre il carrello e trova un totale più basso senza una parola. Repro (b): ospite riempie il carrello col partner A, accede con un account che aveva righe del partner B: MergeCartOnLogin ripassa ogni riga da addItem, `guardSinglePartner` rifiuta quelle di A e il catch a :45 le butta con un `Log::info`. Nota verificata: nel caso del partner passato al pagamento diretto le righe RESTANO davvero (la modalità non è una colonna di catalogo), quindi il difetto è solo su ritiro/sospensione e sul merge.

FIX: -

## [medio] C10 — Partner online ma non più pagabile su Stripe: il muro arriva solo allo step 2, dopo i dati personali, e non si torna indietro

ANCORE: app/Livewire/Commerce/Checkout.php:184 | app/Livewire/Commerce/Checkout.php:214 | app/Livewire/Commerce/Checkout.php:671 | app/Livewire/Commerce/Checkout.php:886 | app/Models/Partner/PartnerProfile.php:143 | app/Services/Partner/PartnerPaymentModeService.php:67 | resources/views/livewire/commerce/checkout.blade.php:152

REPRO: `PartnerProfile::paymentMode()` (:143-146) guarda solo `requiresOnlinePayment()`, quindi `PartnerPaymentModeService::forOwner()` (:67) risponde Online e tutto il catalogo disegna il pulsante carrello. `goToStep(2)` (:184-214) valida i dati, pre-verifica la disponibilità, poi chiama `preparePaymentStep()`; dentro, `initPaymentSession()` trova `! $seller->canBePaid()` a :671, mette `paymentUnavailable = true` e RITORNA — ma `preparePaymentStep()` prosegue e ritorna `true` a :886, quindi `$this->step = 2` viene eseguito a :214. Il blade nasconde Payment Element e «Paga ora» (checkout.blade.php:152 e :188) e `goToStep` accetta solo `$step === $this->step + 1`: non c'è ritorno allo step 1. Repro: partner `online_payment=true` che perde charges_enabled o payouts_enabled dopo la pubblicazione; comprare un suo prodotto, compilare nome/cognome/email/telefono, cliccare «Continua l'acquisto»: si resta su uno step 2 inutilizzabile e i dati vanno riscritti ricaricando.

FIX: `preparePaymentStep()` deve avere un valore di ritorno per «online ma non pagabile» (oggi distingue solo in-struttura sì/no), e canBePaid() va guardato prima, dove si decide se mostrare la CTA.

## [medio] F7 — Il dettaglio di «I miei servizi» non mostra nessuno dei campi nuovi: il partner non può rileggere quello che ha salvato

ANCORE: app/Livewire/Partner/MyServices/PartnerServiceDetail.php:38 | app/Livewire/Partner/MyServices/PartnerServiceDetail.php:64 | app/Livewire/Partner/MyServices/PartnerServiceDetail.php:69 | app/Livewire/Partner/MyServices/PartnerServiceDetail.php:91

REPRO: `rows()` (:38-92) compone dieci righe letterali — tipologia del `type`, nome, luogo (che include address/city/province/zip/license), descrizione, stanze, cancellazione, servizi (services+additional+additional_other+rules), extra (animal_services+animal_services_other), foto, pagamento (account_holder/iban/bic). Non contiene `activity_categories`/`event_categories` né i loro testi liberi, né `operating_area`, `booking_requirement`, `recurrence`, `max_participants`, e nemmeno `date_start/date_end/time_start/time_end`. La riga «Stanze» (:69-75) viene comunque calcolata per un evento e cade su «Non previsto». Repro: pubblicare un evento con tipologie, prenotazione obbligatoria, ricorrenza e 30 posti, aprire «Dettaglio servizio»: nessuno di quei valori è leggibile senza ripercorrere gli undici step.

FIX: -

## [medio] W9 — Ricerca a catalogo sul titolo: whereLike su un path JSON è case-insensitive su SQLite (la suite) e binario su MySQL (la produzione)

ANCORE: app/Livewire/Catalog/Events.php:156 | app/Livewire/Catalog/Events.php:157 | vendor/laravel/framework/src/Illuminate/Database/Query/Grammars/MySqlGrammar.php:65 | vendor/laravel/framework/src/Illuminate/Database/Query/Grammars/SQLiteGrammar.php:54 | app/Services/Partner/Publishing/EventPublisher.php:49

REPRO: `applyWhereFilter()` fa `whereLike('title->'.app()->getLocale(), $term)` (Events.php:156) su una colonna JSON translatable riempita da `translations($draft,'name')` (EventPublisher.php:49). Nel vendor: `MySqlGrammar::whereLike()` (:65-72) con `caseSensitive` falso — il default — compila un `like` nudo, e su MySQL il valore che esce da `json_unquote(json_extract(...))` porta la collation binaria, quindi il LIKE diventa case-sensitive; `SQLiteGrammar::whereLike()` (:54-62) con caseSensitive falso ricade sul `like` di SQLite, che è case-insensitive sull'ASCII. Quindi il test che cerca «weekend» e trova «Weekend di escursioni» passa in suite e la stessa ricerca non trova nulla in produzione. Mascherato in parte dall'OR su `location` (:157), che è una varchar con collation ci. DA VERIFICARE SU MySQL: qui non è eseguibile (host con PHP 7.4, suite su SQLite in memoria). Da segnalare accanto: `operating_area` non entra in nessun ramo del filtro, quindi un professionista che dichiara «Milano e provincia» ma è registrato a Sesto San Giovanni non è trovabile cercando «Milano».

FIX: LOWER() su entrambi i lati o un COLLATE esplicito sul path JSON, e un test che gira anche su MySQL.

## [medio] W7 — Lo step 11 del percorso Struttura raccoglie IBAN/BIC/intestatario solo sulla bozza: il profilo partner resta vuoto e glieli si richiede una seconda volta

ANCORE: app/Livewire/Forms/HotelPaymentForm.php:22 | app/Livewire/Forms/HotelPaymentForm.php:36 | app/Livewire/Partner/Structure/HotelPayment.php:23 | app/Livewire/Forms/PartnerPaymentForm.php:29 | app/Livewire/Forms/PartnerPaymentForm.php:37 | app/Models/Partner/PartnerProfile.php:42 | app/Livewire/Partner/MyServices/PartnerServiceDetail.php:57

REPRO: Anchor corretti: HotelPaymentForm ha 44 righe (non 83). `rules()` :19-26 rende `accountHolder`, `iban`, `bic` tutti required; `toDraft()` :36-43 li scrive su `structure_drafts.account_holder/iban/bic`; `HotelPayment::next()` li salva con `saveStep(..., 11)` a :23. Nessuna riga tocca `partner_profiles`: `PartnerPaymentForm::setFromProfile()` (:29-34) e `toProfile()` (:37-43) lavorano solo sul profilo, i cui fillable stanno a PartnerProfile.php:42-44. Repro: chiudere il wizard struttura digitando l'IBAN, poi aprire Profilo → Metodo di pagamento: vuoto, e lo richiede da capo. Il valore del wizard vive solo come testo nel dettaglio del servizio (PartnerServiceDetail.php:57-61). Correzione al rapporto originale: NESSUNO dei due punti legge davvero quelle coordinate per pagare (i bonifici passano da Stripe Connect), quindi il danno è la doppia digitazione e due copie che non si sincronizzano, non un payout rotto.

FIX: Travasare le coordinate del wizard sul profilo (o leggere il profilo per precompilare lo step): una sola fonte.

## [medio] C3 — I posti si consumano e non si liberano mai: nessun decrement di booked_participants, con la cancellazione gratuita già promessa al cliente

ANCORE: app/Pipes/Order/ReserveAvailabilityPipe.php:66 | app/Services/Availability/AvailabilityService.php:134 | app/Enums/OrderStatus.php:19 | app/Livewire/Partner/Dashboard.php:124 | lang/it/cart.php:49 | lang/it/cart.php:50

REPRO: Verificato il perimetro completo: `grep -rn booked_participants app database` dà sei soli punti — le tre letture (ActivityDetail.php:242, EventDetail.php:136 e :154, AvailabilityService.php:134), i fillable/cast di Event e l'UNICA scrittura, `$event->increment('booked_participants', ...)` a ReserveAvailabilityPipe.php:66. `grep -rn decrement app/` dà solo stepper UI (HasBookingCalendar, HotelRooms, StructureCreate, ActivityCreate): il contatore non viene MAI decrementato. Riclassificazione rispetto al rapporto originale: oggi il buco non perde, perché nessun percorso scrive `OrderStatus::Cancelled` (l'enum esiste a OrderStatus.php:19 e l'unico riferimento in tutto app/ è la LETTURA della dashboard partner a Dashboard.php:124) e perché ogni storno post-capture avviene dopo un rollback che annulla anche l'increment. Il fatto riproducibile OGGI è un altro: il carrello promette al cliente «Cancellazione gratuita — Non oltre 2 settimane prima dell'evento» (lang/it/cart.php:49-50) e non esiste alcun percorso di annullamento. Il giorno in cui atterra, ogni disdetta brucia un posto per sempre. Avvertenza motori: `lockForUpdate` è un no-op su SQLite, quindi l'ordinamento dei lock sotto conc

FIX: Chi scriverà l'annullamento deve liberare i posti nella stessa transazione. Intanto vale la pena decidere se quella riga del carrello può restare.

## [medio] W4 — L'«Indietro» dello step Nome è cablato sullo step del tipo: i due percorsi che lo saltano ci finiscono dentro

ANCORE: resources/views/livewire/partner/activity/activity-name.blade.php:95 | app/Livewire/Partner/CreateService.php:121 | app/Livewire/Partner/CreateService.php:136 | app/Livewire/Partner/Activity/ActivityType.php:37 | lang/it/partner.php:481 | lang/it/partner.php:788 | tests/Feature/Partner/WizardBackLinkTest.php:48

REPRO: `CreateService::next()` scrive `type` a :121-133 e reindirizza DIRETTAMENTE a `partner.activity.name` a :136 per le card «Servizio professionale» ed «Evento», saltando lo step del tipo. Il pulsante «Indietro» di activity-name.blade.php:95 è un href fisso a `route('partner.activity.type')` — l'unico back link del wizard che non passa da `serviceChoiceBackUrl()`, che i tre step del tipo usano invece (activity-type.blade.php:41). Repro: card «Servizio professionale» → prima schermata «Step 2 di 10» (lang/it/partner.php:481) → «Indietro» → si atterra su «Step 1 di 10» (lang/it/partner.php:788), la scelta Attività/Evento che il funnel aveva già fatto: lo skip funziona in una sola direzione e non esiste ritorno alla scelta delle card. `WizardBackLinkTest` copre solo i tre step del tipo (data provider :48-54). Correzione al rapporto originale: la seconda metà («un servizio professionale diventa Evento per un click») NON è una corruzione silenziosa — è una scelta deliberata su uno step legittimo, e `ActivityType::clearedFields()` la gestisce; il difetto è la destinazione del back link.

FIX: Anche ActivityName deve usare serviceChoiceBackUrl() quando è il primo step del percorso (la bozza ci arriva con current_step=1 e senza essere passata da activity.type).

## [medio] W8 — La preselezione della card dalla scelta d'iscrizione (registration_service) non ha UN SOLO test in tutta la suite

ANCORE: app/Livewire/Partner/Registration/PartnerRegisterStep2.php:65 | app/Livewire/Partner/Registration/PartnerRegisterStep2.php:139 | app/Livewire/Partner/CreateService.php:54 | app/Livewire/Partner/CreateService.php:83 | app/Livewire/Partner/CreateService.php:87 | app/Models/Partner/PartnerProfile.php:57

REPRO: `grep -rn registration_service app/ tests/ database/ resources/` restituisce CINQUE occorrenze, tutte in app/, models e migrazione: CreateService.php:81, PartnerRegisterStep2.php:139, PartnerProfile.php:57 e le due della migrazione 2026_09_27_110001. Zero in tests/, zero nelle factory, zero nelle viste. Non sono quindi coperti: che `createAccount()` scriva la colonna (PartnerRegisterStep2.php:139); che la card scelta in iscrizione arrivi preselezionata al primo «Crea servizio» (CreateService.php:54 → :79-92); che un partner iscritto prima della colonna (NULL) non preselezioni nulla invece di lasciare il radio muto — la guardia `in_array($choice, self::SERVICES, true)` a :83 è l'unica cosa che lo impedisce; che la preselezione si spenga dopo la prima scelta (`$alreadyChosen`, :87-92); che gli slug validati in iscrizione (`in:struttura,attivita,servizi,eventi`, PartnerRegisterStep2.php:65) corrispondano alle chiavi delle card. Un refactor che invertisse `$alreadyChosen` o rinominasse 'eventi' passerebbe con 2163 test verdi.

FIX: Tre test: la colonna scritta all'iscrizione; la preselezione al primo ingresso; il silenzio (radio vuoto, nessun errore) con colonna NULL o valore fuori elenco.

## [cosmetico] F3 — Il badge di «I miei servizi» e il banner in dashboard comprimono due cause in una: chi è online ma senza Stripe vede due avvisi per lo stesso fatto

ANCORE: app/Services/Partner/DraftPublicationState.php:115 | app/Services/Partner/DraftPublicationState.php:116 | app/Models/Partner/PartnerProfile.php:124 | app/Livewire/Partner/Dashboard.php:79 | app/Livewire/Partner/Dashboard.php:81 | app/Services/Partner/Publishing/DraftPublisher.php:57 | lang/it/partner.php:38 | lang/it/partner.php:39

REPRO: `canPublishFamily('smartbox')` (PartnerProfile.php:122-127) è `requiresOnlinePayment() && canBePaid()`: un solo booleano per due cause. `DraftPublicationState::state()` (:114-119) guarda `! $canPublishFamily && family === 'smartbox'` a :116 e restituisce AWAITING_PAYMENT_METHOD anche a chi è online e gli manca solo Stripe; `Dashboard::smartboxAwaitingCount()` (:79-86) usa la stessa condizione, mentre `awaitingCount()` (:60) usa `canPublish()`, quindi il partner vede CONTEMPORANEAMENTE il banner Stripe e il banner smartbox. DraftPublisher.php:56-59 e CreatesPartnerService distinguono i due casi con `! requiresOnlinePayment()`, questi due punti no. Riclassificazione: il badge «Serve il sistema di pagamento» non è falso per chi gli manca Stripe (in AnimalAmo il sistema di pagamento È il conto Stripe), e il hint (lang/it/partner.php:39) è solo impreciso; il difetto verificato è la doppia diagnosi sulla stessa pagina. Repro: partner `online_payment=true` con `canBePaid()` false e una bozza smartbox in attesa → due banner in dashboard.

FIX: Riaprire il booleano con requiresOnlinePayment() dove si scegle il MESSAGGIO, come fa il publisher, e contare la smartbox in un solo banner.

## [cosmetico] F8 — animalamo:stuck-drafts: il gruppo «Pronte ma ferme» non esclude le bozze incomplete, e il docblock porta a incolpare il cron

ANCORE: app/Console/Commands/StuckDrafts.php:30 | app/Console/Commands/StuckDrafts.php:68 | app/Console/Commands/StuckDrafts.php:70 | app/Console/Commands/StuckDrafts.php:74 | app/Console/Commands/StuckDrafts.php:76

REPRO: I tre gruppi sono tre filtri indipendenti sullo stesso insieme. Il secondo (:68-72) filtra solo su `canPublishFamily($draft->family()) === true` (:70); il terzo (:74-78) fa `reject(isPublishable)` (:76). Non sono mutuamente esclusivi. Repro: bozza smartbox in attesa senza `price` (o struttura senza `rooms`) di un partner pagabile → compare sotto «Pronte ma ferme: il partner può già pubblicare» E sotto «In attesa ma incomplete». Chi legge segue il docblock (:30-36), lancia `animalamo:publish-awaiting-drafts`, non vede pubblicare nulla e conclude che manca `schedule:run`, mentre la causa è scritta due righe più sotto.

FIX: Aggiungere `isPublishable` al filtro del secondo gruppo, così i tre insiemi sono disgiunti.

## [cosmetico] W6 — Lo step 9 «Smartbox» del percorso Struttura è interamente inerte: smartbox_consent e smartbox_types non hanno nessun consumatore

ANCORE: app/Livewire/Partner/Structure/HotelSmartbox.php:33 | app/Services/Partner/Publishing/StructurePublisher.php:21 | app/Services/Partner/Publishing/StructurePublisher.php:48 | app/Livewire/Partner/Smartbox/SmartboxStructures.php:58 | app/Livewire/Partner/MyServices/PartnerServiceDetail.php:38

REPRO: `HotelSmartbox::next()` (:33) salva `smartbox_consent` e le quattro `smartbox_types`. `grep -rn 'smartbox_consent|smartbox_types' app resources tests` mostra che NESSUN publisher le legge: compaiono solo negli step (wizard e pannello), nei fillable/cast della bozza, in ServiceOptionLabels e in `ActivityType::clearedFields()` (:69). `StructurePublisher::publish()` (:21-52) non le nomina e `syncAmenities` (:48-52) usa services/additional/animal. La scelta delle strutture di un cofanetto si fa dall'altro lato, sulle proprie strutture pubblicate (SmartboxStructures.php:58), quindi l'adesione dichiarata non viene mai onorata. Non compaiono nemmeno in `PartnerServiceDetail::rows()` (:38-92). REFUTATE invece le altre sottotesi del rapporto originale: `license` è `required` (HotelLocationForm.php:45) e non arriva a `structures`, ma è VISIBILE al partner nella riga «Luogo» (PartnerServiceDetail.php:48) e un CIR fuori dal B2C è una scelta difendibile; `rules` e `additional_other` compaiono nella riga «Servizi» (:79-80) e `animal_services_other` nella riga «Extra» (:84); il dettaglio delle camere ridotto al minimo in cents è documentato come «A partire da» (StructurePublisher.php:30-32). L'af

FIX: O si collega l'adesione dichiarata alla selezione del cofanetto, o lo step va tolto: undici risposte che nessuno legge sono solo undici occasioni di sbagliare.

## [cosmetico] F9 — Il pannello accoda max:110 a location.meetingPoint.it/en, chiavi che sul ramo attività il Form non dichiara, e duplica l'exists sulla provincia

ANCORE: app/Livewire/Admin/Catalog/ActivityCreate.php:207 | app/Livewire/Admin/Catalog/ActivityCreate.php:208 | app/Livewire/Admin/Catalog/ActivityCreate.php:209 | app/Livewire/Forms/ActivityLocationForm.php:61 | app/Livewire/Forms/ActivityLocationForm.php:65 | app/Livewire/Forms/ActivityLocationForm.php:72 | app/Livewire/Admin/Catalog/StructureCreate.php:265

REPRO: `ActivityLocationForm::rules()` (:56-76) dichiara `meetingPoint.it/en` SOLO dentro `if ($this->isEvent)` (:65-69); sul ramo attività (il valore di partenza della pagina) dichiara `operatingArea.it/en` (:72-73). I due `[]=` di ActivityCreate.php:208-209 auto-creano quindi `location.meetingPoint.it => ['max:110']` e `.en => ['max:110']`: regole su un campo che la vista non disegna. Oggi innocue — la property del Form esiste con `['it'=>'','en'=>'']`, `max` non è implicita e la stringa vuota la salta — ma un `required` o un `Rule::in` aggiunto domani a quelle chiavi bloccherebbe il salvataggio di un'attività su un campo invisibile, senza che nessun test lo noti. Nella stessa lista, :207 accoda `Rule::exists('provinces','short_name')` a `location.province`, che il Form ha già a :61: duplicato innocuo (MessageBag deduplica il messaggio identico) ma StructureCreate.php:255-265 lo documenta come consapevole e ActivityCreate no.

FIX: Condizionare i due rafforzamenti del punto d'incontro allo stesso `isEvent` del Form, e togliere (o commentare) l'exists duplicato.

## [cosmetico] W10 — DUPLICATO di F5 — stessa riga, stesso difetto: non contarlo due volte

ANCORE: app/Livewire/Partner/Structure/StructureType.php:57 | app/Livewire/Partner/Structure/StructureType.php:58

REPRO: W10 e F5 descrivono la stessa lista letterale alla stessa riga (StructureType.php:58) con la stessa causa e la stessa cura. W10 la inquadra come latente («oggi nessun danno perché StructurePublisher non nomina quelle colonne»), F5 come attiva («family() resta attivita, quindi pubblica EventPublisher e i posti passano»): F5 ha ragione, e la verifica lo conferma — `StructureType::next()` (:37) riscrive `type` ma non `service_category`, quindi la bozza resta sulla famiglia attività. Tenere F5 e chiudere W10 come duplicato: mandare due volte lo stesso file a riscrivere è esattamente il costo che questa fase deve evitare.

FIX: Vedi F5.

