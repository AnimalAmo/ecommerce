# Messa online su animalamo.it

Data: 2026-09-08

Runbook della pubblicazione. Nasce da un audit di pre-produzione (env, contenuti
residui, deploy, legale/SEO, sicurezza) fatto sul repo l'08/09/2026, e dalle
decisioni prese lo stesso giorno:

1. **Vetrina reale**: il catalogo mock dell'XD non va in produzione. Online ci
   sono home, Animal Times, pagine legali, registrazione B2C e area partner; il
   catalogo si popola con i partner veri.
2. **Bloccanti chiusi prima dello switch DNS**, non dopo.
3. **Deploy dal branch `main` del repo cliente** (AnimalAmo/ecommerce).
4. **Rinomina del sito Forge di staging**: si porta dietro database, storage e
   dati già inseriti — quindi il DB va ripulito, non ricreato (vedi § 3).

## 1. Prima dello switch — cose che deve fare il cliente

Nessuna di queste è codice: senza, il sito va online sbagliato.

### Iubenda (prima del cambio DNS, non dopo)

Il documento cookie e il banner di consenso sono registrati sull'host di
staging: il sito Iubenda è `4672050`, la cookie policy `99317099`, e il
documento si intitola letteralmente **«Cookie Policy di
ecommerce-0tepvdzv.on-forge.com/»**. Serve, nella dashboard Iubenda:

- cambiare l'indirizzo del sito da `ecommerce-0tepvdzv.on-forge.com` a
  `animalamo.it` e rigenerare la cookie policy — l'id resta lo stesso, quindi
  `config/services.php` non cambia; ricontrollare il titolo dopo;
- pubblicare la **versione inglese** dei documenti: oggi
  `csLangConfiguration` contiene solo `it`, e mezzo sito è in inglese. Iubenda
  serve le lingue come documenti distinti (un secondo id), non con un
  parametro nell'URL: quando arriva l'id inglese, il link nei footer diventa
  una mappa per lingua;
- sapere che il consenso raccolto finora è archiviato sull'origine di staging:
  **al cambio dominio il banner ricomparirà a tutti**. È corretto, ma va detto.

### Stripe

- passare alle chiavi live e metterle nel `.env` di produzione (le inserisci tu
  dall'editor di Forge: non passano da qui);
- creare il webhook su `https://animalamo.it/webhooks/stripe` con gli eventi
  `payment_intent.succeeded` e `payment_intent.payment_failed`, e copiare il
  `whsec_…` in `STRIPE_WEBHOOK_SECRET`. Senza, i pagamenti normali si
  concludono lo stesso (la cattura è verificata lato server), ma tutto ciò che
  si chiude dopo — 3DS, retry dei wallet — resta `Pending` per sempre e il
  cliente non riceve la mail;
- registrare `animalamo.it` in **Stripe → Settings → Payment method domains**,
  altrimenti i pulsanti Apple Pay e Google Pay del checkout restano spenti.

### Google Maps

La chiave `GOOGLE_MAPS_KEY` viaggia in chiaro nell'URL dell'iframe: se ha
restrizioni per referrer vanno aggiornate con il nuovo dominio, altrimenti ogni
scheda mostra una mappa vuota.

## 2. DNS su Register.it

Il dominio è del cliente, gestito su Register.it. Records da mettere:

| Tipo | Host | Valore | Perché |
|---|---|---|---|
| A | `@` | IP del server Forge | il sito |
| A | `www` | IP del server Forge | Forge serve entrambi |
| NS | `mg` | `ns1.digitalocean.com`, `ns2…`, `ns3…` | **ripristina Mailgun** |

Attenzione a due cose misurate, non ipotizzate:

- **`mg.animalamo.it` non è più delegato.** SPF e DKIM esistono ancora, ma solo
  dentro una zona DigitalOcean orfana: sul DNS pubblico `mg` risolve su un
  wildcard `A` di Register.it (`195.110.124.148`), e `dig TXT mg.animalamo.it`
  torna vuoto. Finché non si rimette la delega NS, **ogni mail transazionale
  parte non autenticata** — conferme d'ordine, reset password, inviti partner.
- **C'è un wildcard `*.animalamo.it`** che risponde a qualsiasi sottodominio
  non dichiarato: maschera gli errori di configurazione, perché tutto "risolve"
  comunque. Dichiarare esplicitamente ciò che serve.

Consigliati, non bloccanti: SPF e DMARC sul dominio apex.

## 3. Sul server Forge

Si rinomina il sito esistente, quindi database e storage restano — con dentro
il catalogo mock e gli utenti demo. Vanno tolti a mano: il seeder ora non li
ricrea (commit `0a62b66`), ma non cancella ciò che c'è già.

### 3.1 `.env` di produzione

```
APP_ENV=production          # stringa esatta: AppServiceProvider forza https solo su "production"
APP_DEBUG=false
APP_URL=https://animalamo.it
SEED_DEMO_DATA=false
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
QUEUE_CONNECTION=database   # insieme al worker del § 3.3
CONTACT_RECIPIENT=          # dove arrivano i messaggi di "Contattaci"
STRIPE_KEY= / STRIPE_SECRET= / STRIPE_WEBHOOK_SECRET=
MAILGUN_DOMAIN=mg.animalamo.it
```

`APP_KEY` va generata sul server, non copiata da un altro ambiente.

### 3.2 Script di deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan view:cache
php artisan queue:restart
```

Tre trappole verificate:

- **`auth.json` è gitignored** e Flux Pro sta su un repo privato: senza le
  credenziali sul server, `composer install` si pianta. Vanno messe una volta
  (`composer config --global`, o il file `auth.json` nella cartella del sito).
- **`public/build` è gitignored**: senza `npm run build` nello script ogni
  pagina è un 500. Non è opzionale.
- **Mai `php artisan optimize` né `route:cache`**: le rotte sono localizzate
  con `LaravelLocalization::transRoute`, e la cache ne congela una lingua sola
  mandando in 404 il resto del sito. `config:cache` e `view:cache` invece vanno.

### 3.3 Daemon della coda

Tutte le mail dell'app sono `ShouldQueue`. Con `QUEUE_CONNECTION=database` e
nessun worker **non parte niente e non si vede nessun errore**: restano righe
nella tabella `jobs`. Creare su Forge un daemon:

```
php artisan queue:work --tries=3 --timeout=60
```

e tenere d'occhio `failed_jobs`: il listener degli ordini ingoia gli errori di
invio per non far fallire l'acquisto, quindi un mailer rotto è invisibile
dall'interfaccia.

**Prima di avviare il daemon, svuotare la coda.** Su staging il worker non c'è
mai stato, quindi ogni mail accodata in mesi di prove è ancora nella tabella
`jobs`: al primo `queue:work` partirebbero tutte insieme, dal dominio nuovo,
verso indirizzi veri — conferme di ordini che non esistono e inviti a diventare
partner. È il singolo passo più rischioso dell'intera messa online.

```bash
php artisan queue:clear        # svuota jobs; poi si avvia il daemon
```

### 3.4 Pulizia del database ereditato dallo staging

Una volta sola, **dopo** `db:seed --force` e prima di puntare il dominio.
L'ordine conta: `RegionSeeder` gira sempre (sta sopra il guard demo) e riscrive
`regions.structures_count` coi numeri del mock, quindi il seed va prima.

```bash
php artisan animalamo:purge-mock-catalog --dry-run   # conta e non tocca niente
php artisan animalamo:purge-mock-catalog             # chiede conferma in produzione
```

Il comando cancella solo ciò che è identificabile con certezza: catalogo senza
bozza dietro e senza proprietario (o intestato alle persone demo), i due
account demo con i loro ordini, pagamenti e bozze, i post della community
seminati, i luoghi del mock, e azzera `regions.structures_count`. Il criterio
è `structure_draft_id`: i publisher lo scrivono sempre, i seeder mai — serve
perché il wizard partner è stato pubblico fino all'08/09/2026 e in quella
finestra salvava bozze da ospite, quindi esiste contenuto vero con `user_id`
nullo. Il `--dry-run` avverte se restano righe con una bozza dietro.

**Quello che il comando NON tocca, di proposito**, perché ogni riga può
appartenere a una persona vera e cancellarla è irreversibile. Va guardato a
mano, riga per riga, prima dello switch:

```sql
-- account registrati su staging fuori dai due demo
SELECT u.id, u.email, u.is_active, u.created_at, GROUP_CONCAT(r.name) ruoli
FROM users u
LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'user'
LEFT JOIN roles r ON r.id = mhr.role_id
WHERE u.email NOT IN ('giulia.rossi@gmail.com','partner@animalamo.test')
GROUP BY u.id ORDER BY u.created_at;

-- catalogo pubblicato da chi ha provato il wizard (sopravvive alla pulizia)
SELECT 'structures' t, id, user_id, structure_draft_id, created_at FROM structures WHERE structure_draft_id IS NOT NULL
UNION ALL SELECT 'events', id, user_id, structure_draft_id, created_at FROM events WHERE structure_draft_id IS NOT NULL
UNION ALL SELECT 'smartbox', id, user_id, structure_draft_id, created_at FROM smartbox_packages WHERE structure_draft_id IS NOT NULL;

-- candidature "Lavora con noi", messaggi "Contattaci", post community
SELECT id, first_name, last_name, email, status, created_at FROM partner_applications ORDER BY id;
SELECT id, first_name, last_name, email, LEFT(message,60) messaggio, created_at FROM contact_messages ORDER BY id;
SELECT id, user_id, author_name, title, created_at FROM community_posts ORDER BY id;

-- bozze abbandonate: contengono IBAN e dati fiscali in chiaro
SELECT id, user_id, status, current_step, city, iban, created_at FROM structure_drafts ORDER BY id;
```

Cancellare una bozza a cui punta una riga di catalogo viva rompe la modifica
B2B di quel partner (`structure_draft_id` è nullOnDelete): escludere sempre le
bozze referenziate.

Igiene a rischio zero, da fare comunque: `TRUNCATE sessions;`,
`TRUNCATE password_reset_tokens;`, `TRUNCATE cache;`, `TRUNCATE cache_locks;`.
Le foto dei tester restano su disco in `storage/app/public/structure-photos` e
`smartbox-photos`: vanno confrontate con le righe sopravvissute prima di
cancellarle, e `livewire-tmp` si svuota senza condizioni.

Verifica di chiusura: `SELECT reviewable_type, COUNT(*) FROM reviews GROUP BY 1;`
deve tornare vuota, la home non deve contenere né «Sofia» né un badge
«N Strutture», e `/animal-holiday` non deve promettere strutture.

## 4. Git — creare `main` sul repo cliente

Due remote: `origin` (algomeraIT/animal_amo) e `animalamo` (AnimalAmo/ecommerce),
entrambi con `develop` già aggiornato. `main` **non è semplicemente indietro**:
ha 12 commit in meno di `develop` ma anche 24 che `develop` non ha — 23 sono
merge di PR `develop → main` e uno è un `fix` fatto a mano sul footer. Quindi
**non è un fast-forward**, è un merge vero. Provato a vuoto con `git merge-tree`:
zero conflitti.

Comandi (li esegui tu, come deciso):

```bash
git fetch --all
git checkout -B main animalamo/main
git merge develop -m "Merge develop into main for the animalamo.it launch"
git push animalamo main
git push origin main
git checkout develop
```

In alternativa, se preferisci la storia che il repo ha già: aprire la PR
`develop → main` su GitHub, come le 23 precedenti.

## 5. Dopo lo switch

- certificato Let's Encrypt su `animalamo.it` e `www` (Forge lo fa dal pannello);
- **impedire l'indicizzazione dello staging**, se resta acceso: oggi è
  crawlabile e duplicherebbe la produzione;
- provare una mail vera end-to-end (registrazione o reset password) e cercare
  l'evento `delivered` nei log Mailgun, non fermarsi al 200 dell'API;
- provare un pagamento live da 1 € e verificare che il webhook arrivi;
- controllare che una pagina che va in errore mostri la pagina generica e non
  lo stack trace.

## 6. Resta aperto (non blocca il lancio, ma è debito noto)

- **SEO**: nessuna meta description, nessun Open Graph, nessun canonical,
  nessun `hreflang` it/en, nessuna `sitemap.xml`. Le condivisioni social escono
  vuote.
- **Cookie policy solo in italiano** finché il cliente non pubblica il
  documento inglese (vedi § 1).
- **Tidio** si carica prima del consenso, fuori dal blocco di Iubenda, mentre
  la cookie policy lo classifica come soggetto a consenso.
- Le password richiedono solo 8 caratteri e la registrazione non chiede
  l'accettazione esplicita di privacy e termini.
- IBAN, partita IVA, codice fiscale e PEC dei partner sono in chiaro nel DB.
- La pagina FAQ non esiste: «Aiuto/FAQ» è un `href="#"` in tre punti dell'area
  partner.
