# Runbook — togliere l'incasso online a un partner senza Stripe

**Caso:** «Bio Boutique Laurino» è a incasso online in produzione ma non ha mai collegato Stripe, quindi
le sue schede portano il cliente a un checkout che non può funzionare (26/09/2026).

**Il partner non è nel database di sviluppo.** Cercato in `structures`, `events`, `smartbox_packages`,
`structure_drafts` e `partner_profiles` con `%aurino%`, `%outique%` e `%Bio%`: zero righe. In locale c'è
un solo `partner_profiles`, «Hotel Rosovino Milano» del seed demo. Quindi è un dato di produzione e va
toccato là.

---

## 0. Il perimetro, prima di tutto

La modalità di pagamento **è del partner, non della singola scheda.** La colonna è
`partner_profiles.online_payment`: non esiste da nessuna parte una modalità per struttura. Togliere
l'incasso online a Laurino sposta a pagamento diretto **tutte** le sue schede — strutture, eventi,
attività, smartbox. Confermato con il committente il 26/09/2026.

Se in futuro servisse per singola scheda, è sviluppo: colonna nuova più la lettura cambiata in cinque
punti (`PartnerContacts`, `CartManager`, `Checkout`, i publisher, le viste catalogo).

---

## 1. La strada consigliata: il pannello admin

**Esiste già in produzione e non richiede un deploy.** È la via da usare adesso.

1. entra in `/admin/users/{id}` con un'utenza **superadmin** (la rotta è dietro il middleware
   `superadmin`, `routes/admin.php:46`);
2. nella scheda del partner, accanto a «Pagamento», premi **«Cambia»**
   (`resources/views/livewire/admin/people/user-show.blade.php:143`);
3. nel modale scegli **«Direttamente al partner»** e **compila il campo del sito** dove pagare o
   prenotare (vedi la trappola in §4.5);
4. salva. Il badge della scheda passa a «Direttamente al partner» e compare il toast di conferma.

Il modale precompila il campo del sito col valore già salvato, quindi non si perde il link per
distrazione (`app/Livewire/Admin/People/UserShow.php:74`). Il bottone non compare se l'utente non ha
ancora una riga `partner_profiles`.

Il partner può farlo anche da sé, da `/partner/profilo/metodo-pagamento` — utile da dirgli, così sa
come tornare online quando aprirà Stripe. Non serve usare le sue credenziali per questo intervento.

## 2. L'alternativa da riga di comando

Ho aggiunto `animalamo:payment-mode` ([PaymentModeCommand.php](app/Console/Commands/PaymentModeCommand.php)),
con dieci test ([PaymentModeCommandTest.php](tests/Feature/Partner/PaymentModeCommandTest.php)).
**Richiede un deploy**, quindi oggi la via del pannello è più rapida; il comando serve quando la
modifica va fatta senza interfaccia, o ripetuta.

```bash
# senza --force: stampa il quadro e chiede conferma. In produzione si usa così.
php artisan animalamo:payment-mode "Bio Boutique Laurino" --offline --url=https://…
```

Stampa prima di scrivere:

```
Bio Boutique Laurino (profilo #12, utente #34)
Modalità attuale: incasso online su AnimalAmo
Stripe: non operativo, non può essere bonificato
A catalogo: 2 strutture, 1 eventi, 0 smartbox — 3 schede in tutto
Nuova modalità: pagamento diretto al partner
Confermi il passaggio a pagamento diretto? (yes/no)
```

Regole del comando:
- accetta ragione sociale, email del titolare o id del profilo;
- **rifiuta un nome ambiguo** invece di scegliere: se il testo corrisponde a più partner stampa i
  candidati ed esce senza scrivere. Attenzione: un nome parziale che corrisponde a **un solo** partner
  passa — perciò leggi la ragione sociale nell'anteprima prima di confermare, e non usare `--force`;
- se ometti `--url` tiene il sito già salvato, non lo cancella;
- `--online` esiste per il ritorno, e lo rifiuta se Stripe non è operativo: la regola sta nel service,
  il comando non la scavalca.

Collaudato in locale contro MySQL sul profilo demo: passaggio a diretto, verifica a database, ritorno a
online, profilo lasciato come era (`online_payment = 1`, `payment_url` NULL).

**Non fare una UPDATE a mano.** Salterebbe la validazione del link (finisce in un `href` nelle pagine
B2C e nelle mail) e il rilascio delle bozze in attesa. E se la si fa, deve essere `SET online_payment = 0`:
`requiresOnlinePayment()` è `online_payment !== false`, quindi un NULL verrebbe riletto come **online**.

---

## 3. Da controllare PRIMA, sul database di produzione

Sostituisci `:uid` con `partner_profiles.user_id` trovato al primo passo.

```sql
-- 1. Chi è, e com'è oggi
SELECT id, user_id, business_name, online_payment, payment_url,
       stripe_account_id, stripe_charges_enabled, stripe_payouts_enabled
FROM partner_profiles WHERE business_name LIKE '%aurino%';

-- 2. Quante schede sposta (il comando lo stampa da sé)
SELECT 'structures' t, COUNT(*) c FROM structures WHERE user_id = :uid
UNION ALL SELECT 'events', COUNT(*) FROM events WHERE user_id = :uid
UNION ALL SELECT 'smartbox', COUNT(*) FROM smartbox_packages WHERE user_id = :uid;

-- 3. BLOCCANTE se torna righe: carrelli aperti con i suoi prodotti.
--    Le righe regalo (is_gift = 1) vengono rifiutate al secondo passo del
--    checkout appena il partner è a pagamento diretto.
SELECT ci.cart_id, ci.purchasable_type, ci.purchasable_id, ci.is_gift
FROM cart_items ci WHERE ci.partner_user_id = :uid;

-- 4. Ordini suoi non ancora chiusi: nascono con la modalità copiata sulla
--    testata, quindi restano leggibili — ma guardali prima di cambiare.
SELECT o.id, o.order_number, o.status, o.payment_mode, o.created_at
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
WHERE oi.partner_user_id = :uid AND o.status NOT IN ('completed', 'cancelled')
GROUP BY o.id ORDER BY o.created_at DESC;

-- 5. Bozze in attesa: queste ANDRANNO ONLINE subito dopo la modifica
SELECT id, status, current_step, publish_requested_at
FROM structure_drafts WHERE user_id = :uid AND publish_requested_at IS NOT NULL;
```

Se la 2 conta **smartbox > 0**, fermati e parlane con la cliente: una smartbox è un cofanetto prepagato
e col pagamento diretto non è acquistabile. La strada giusta lì è non pubblicarla, non mostrare i
contatti (è la domanda 9 della mail del 26/09).

---

## 4. Trappole

### 4.1 È una porta a senso unico, per questo partner

`canSwitchToOnline() = requiresOnlinePayment() || canBePaid()` (`PartnerProfile.php:92-95`). Un partner
già a pagamento diretto **e** senza Stripe operativo dà `false || false`: **non può tornare all'incasso
online** né dal pannello né col comando, finché non completa l'onboarding Stripe. Per Laurino è
esattamente la situazione (Stripe non c'è), quindi va detto al partner: «quando aprirai il conto Stripe
potrai rimettere l'incasso online da solo, prima no».

Non è un difetto: è la protezione che evita di rimettere online un partner che non può incassare.

### 4.2 Le liste continuano a offrire "Aggiungi al carrello"

Il pulsante spento è quello delle **schede**. Sulla pagina Eventi
(`resources/views/livewire/catalog/events.blade.php:193`) e sulla pagina di una regione
(`animal-holiday-region.blade.php:213`) la card chiama `addToCart()` senza leggere la modalità, e
`AddsEventToCart::addToCart()` filtra solo `hasJoinCta()` — poi `CartManager::addItem()` non ha alcuna
guardia sul pagamento diretto. È una scelta dichiarata nel codice: leggere la modalità lì costerebbe una
query per riquadro (`partials/catalog/pay-on-site-notice.blade.php:40-44`).

Conseguenza operativa: dopo la modifica un visitatore può ancora mettere nel carrello un evento a
pagamento di questo partner partendo da una lista, e arriva al percorso "paghi in struttura". Nessuno
paga online e nulla si rompe, ma se la cliente guarda la lista vede un pulsante che la scheda non ha
più. Aspettati la domanda.

### 4.3 Il carrello regalo di questo partner si blocca

`CartManager::addItem()` rifiuta un regalo per un partner a pagamento diretto, e `Checkout` rifiuta di
aprire il secondo passo per un carrello regalo già pieno: il cliente legge «rimuovilo dal carrello» e
deve farlo a mano, nessuno lo toglie per lui. Se Laurino vende smartbox o regali, controlla la query 3
prima di procedere.

### 4.4 Col pagamento diretto il login diventa obbligatorio

Nel ramo "paghi in struttura" l'ospite non autenticato viene mandato al login quando entra nel secondo
passo (`Checkout.php:811-817`), mentre con l'incasso online poteva pagare da ospite. È un attrito in più
per il cliente finale, non un errore.


### 4.5 Senza `payment_url` la scheda non offre alcun modo di prenotare

Con il pagamento diretto il box prenotazione lascia il posto alla card «Contatti», costruita con
ragione sociale, indirizzo e **solo** `payment_url` come sito (`PartnerContacts.php:46`). Telefono,
email pubblica e orari **non esistono a database**. Se il campo del sito resta vuoto, il visitatore
legge nome e indirizzo e nulla più. Chiedi il link al partner e mettilo nello stesso salvataggio.

### 4.6 Le sue bozze ferme vanno online da sole

`set()` fa `PublishAwaitingDrafts::dispatch()` quando il partner diventa pubblicabile, e un partner a
pagamento diretto **è** pubblicabile (`canPublish() = !requiresOnlinePayment() || canBePaid()`). Oggi
Laurino, online e senza Stripe, non può pubblicare: è probabile che abbia schede finite e mai uscite.
Dopo la modifica escono. È l'effetto voluto — sappilo prima, così non sembra un incidente.

Con la UPDATE a mano il dispatch non parte, ma
`animalamo:publish-awaiting-drafts` rifà lo stesso lavoro ogni dieci minuti: al massimo un ritardo,
**se il cron gira in produzione** — cosa ancora da verificare (è uno dei quattro controlli server della
mail del 26/09).

### 4.7 Chi è già dentro il checkout tiene il ramo vecchio

`Checkout` fissa il ramo di pagamento quando il cliente entra nel secondo passo e non lo rilegge più
(`Checkout.php:133`, `:777`). Chi sta pagando nell'istante della modifica finisce come ha iniziato.
Fallo in un'ora tranquilla.

### 4.8 Nessuno registra chi ha cambiato cosa

Né il pannello né il service scrivono un log di successo, e `PartnerProfile` non ha observer: l'unico
`Log` sul percorso è un warning se il dispatch delle bozze fallisce. Annota tu data, ora e chi ha fatto
la modifica — dopo non è ricostruibile.

---

## 5. Dopo la modifica

```sql
SELECT business_name, online_payment, payment_url FROM partner_profiles WHERE user_id = :uid;
```

`online_payment` deve essere `0` e `payment_url` valorizzato.

Poi, da riga di comando:

```bash
php artisan animalamo:connect-readiness
```

Il partner **non** deve più comparire fra quelli con onboarding Stripe incompleto: a pagamento diretto
non gli serve.

A browser: apri una sua scheda pubblica. Al posto del box prenotazione ci deve essere la card
«Contatti», e il pulsante di acquisto non ci deve più essere — nemmeno la barra fissa in basso da
telefono.

**Rollback:** stessa strada al contrario, dal pannello o con `--online` — ma **solo quando Stripe è
operativo** (vedi §4.1). Per Laurino, oggi, il ritorno non è possibile: è la regola del service, non un
limite del comando. Se serve annullare prima che Stripe esista, l'unica strada è una UPDATE a mano
(`SET online_payment = 1`), e a quel punto le sue schede tornano a puntare a un checkout che non
funziona — cioè il problema di partenza.
