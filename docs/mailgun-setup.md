# Mailgun — configurazione invio email

Provider SMTP scelto dalla cliente (lug 2026) per tutte le email del portale:
conferma ordine, regalo Smartbox, invito partner B2B.

Lato codice è già tutto pronto: restano solo le credenziali e i record DNS.

---

## 0ter. Stato al 29 lug 2026 (fine giornata) — invio funzionante

`mg.animalamo.it` è **verificato e attivo** in regione EU, e le email partono e
vengono consegnate. Il sandbox non serve più.

| Cosa | Stato |
|:--|:--|
| Mailgun `mg.animalamo.it` (EU) | `active`, 5/5 record verificati, DKIM selector `mta` (2048 bit) |
| Zona `mg.animalamo.it` | su DigitalOcean, creata via API |
| Delega NS su Register.it | pubblicata: host `mg` → `ns1/2/3.digitalocean.com` |
| Invio | `delivered` su Gmail, sia via API diretta sia via `php artisan mail:test` |
| `mg.animalamo.com` | cancellato da Mailgun (resta una zona orfana su DO, innocua) |

Configurazione in `.env` (locale e, da replicare, sui server):

```dotenv
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.animalamo.it
MAILGUN_ENDPOINT=api.eu.mailgun.net
MAIL_FROM_ADDRESS="no-reply@mg.animalamo.it"
MAIL_FROM_NAME="AnimalAmo"
```

> **Le sending key sono legate a un dominio.** Passando dal sandbox a
> `mg.animalamo.it` la vecchia key continua a rispondere `HTTP 200` sulle
> letture (`GET /v3/domains/…`) ma rifiuta l'invio con un laconico
> `Forbidden (code 401)`. Va generata una sending key **per il nuovo dominio**
> (Settings → API keys → Add new key → Sending key → domain) e sostituita in
> `MAILGUN_SECRET`. Un permesso parziale è più insidioso di una key invalida:
> tutto sembra configurato.

Cosa resta prima del go-live: `queue:work` sotto supervisor sui server (§7),
`.env` aggiornato su demo/produzione, e la valutazione del piano Mailgun (§7).

## 0bis. Stato al 29 lug 2026

Riverificato: nulla è cambiato sul fronte DNS, `animalamo.com` resta NXDOMAIN.
Sull'account Mailgun risultano solo `sandbox…9.mailgun.org` (US, `active`) e
`mg.animalamo.com` (EU, `unverified`). Invio sandbox riprovato: consegnato.

Deciso il dominio di produzione: **`mg.animalamo.it`, regione EU**. Due blocchi
aperti, entrambi di credenziali/accessi, non di codice:

1. **Creazione del dominio su Mailgun.** La chiave in `.env` è una *sending key*
   (scope singolo dominio): `POST /v3/domains` risponde
   `API key does not have sufficient permissions`. Serve una **Private API key**
   (Settings → API keys) o che il dominio venga creato dalla dashboard.
2. **Pubblicazione dei record DNS.** Vedi §8.3bis: l'autorità di `animalamo.it`
   è Register.it, quindi l'accesso a DigitalOcean da solo non basta.

## 0. Stato al 28 lug 2026

L'account Mailgun esiste ed è raggiungibile, ma **nessun dominio custom è
verificabile allo stato attuale**:

| Cosa | Stato |
|:--|:--|
| Dominio Mailgun `mg.animalamo.com` | creato in regione **EU**, `unverified` |
| DNS `animalamo.com` | **non registrato** — `dig NS animalamo.com` → `NXDOMAIN` dai server `.com` |
| DNS `animalamo.it` | registrato, NS su `ns1/ns2.register.it` (A `195.110.124.133`, MX `mail.register.it`) |
| Sandbox `sandbox…9.mailgun.org` | regione **US**, `active`, 0 credenziali SMTP |
| Demo | `https://ecommerce-0tepvdzv.on-forge.com/` (sottodominio Forge, nessun dominio custom) |

Due conseguenze:

1. `mg.animalamo.com` non si verificherà mai finché `animalamo.com` non viene
   registrato: NXDOMAIN al registry significa che il dominio non esiste, quindi
   non c'è zona in cui pubblicare SPF/DKIM. Una zona DNS creata su DigitalOcean
   **non registra il dominio** e resta inerte finché il registrar non delega gli NS.
2. Il dominio buono è `animalamo.it`, ma è delegato a Register.it. Per gestirne
   il DNS su DigitalOcean vanno prima ricreati su DO **tutti** i record esistenti
   (A del sito, MX di Register.it) e solo dopo cambiati gli NS sul registrar,
   altrimenti sito ed email attuali si rompono durante la propagazione.

Decisione presa: **sandbox per il demo adesso**, dominio reale + delega a
DigitalOcean in un secondo momento (vedi §5b).

## 1. Cosa serve dalla cliente

| Dato | Dove si trova su Mailgun | Va in |
|:--|:--|:--|
| Sending domain (es. `mg.animalamo.it`) | Sending → Domains | `MAILGUN_DOMAIN` |
| SMTP username (es. `postmaster@mg.animalamo.it`) | Domain settings → SMTP credentials | `MAIL_USERNAME` |
| SMTP password | idem (visibile **una sola volta**, poi si rigenera) | `MAIL_PASSWORD` |
| Sending API key (`key-…`) | Settings → API keys | `MAILGUN_SECRET` |
| Regione dell'account | banner in alto nella dashboard (US / EU) | vedi §2 |

Serve anche l'accesso al DNS del dominio `animalamo.it` per pubblicare i record del §3.

> **Regione**: un dominio creato nell'area **EU** non risponde sugli endpoint US
> e viceversa. La configurazione di default del progetto è **EU** — coerente col
> GDPR, visto che passano indirizzi email di utenti italiani. Se la cliente ha
> già aperto l'account in area US, vanno cambiate `MAIL_HOST` e `MAILGUN_ENDPOINT`.

## 2. Endpoint per regione

|  | EU (default del progetto) | US |
|:--|:--|:--|
| SMTP host | `smtp.eu.mailgun.org` | `smtp.mailgun.org` |
| API endpoint | `api.eu.mailgun.net` | `api.mailgun.net` |

Porte SMTP: **587** con STARTTLS (consigliata), **2525** se l'hosting filtra la
587, **465** solo con `MAIL_SCHEME=smtps`.

## 3. Record DNS (a cura di chi gestisce il dominio)

Mailgun li elenca in *Domain settings → DNS records*. Vanno tutti in **verde**
prima di uscire dalla sandbox:

- **TXT** `mg.animalamo.it` → `v=spf1 include:eu.mailgun.org ~all` (SPF)
- **TXT** `<selector>._domainkey.mg.animalamo.it` → chiave DKIM fornita da Mailgun
- **MX** `mg.animalamo.it` → `mxa.eu.mailgun.org` e `mxb.eu.mailgun.org` (priorità 10)
- **CNAME** `email.mg.animalamo.it` → `eu.mailgun.org` (tracking click/aperture)
- consigliato: **TXT** `_dmarc.animalamo.it` → `v=DMARC1; p=none; rua=mailto:…`

Finché il dominio non è verificato, Mailgun consegna **solo** ai destinatari
inseriti manualmente negli *Authorized Recipients* del dominio sandbox.

## 4. Variabili `.env`

Per staging/produzione, via SMTP (quello chiesto dalla cliente):

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.eu.mailgun.org
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=postmaster@mg.animalamo.it
MAIL_PASSWORD=<smtp password>
MAIL_TIMEOUT=10
MAIL_FROM_ADDRESS="no-reply@mg.animalamo.it"
MAIL_FROM_NAME="AnimalAmo"
```

In locale si lascia `MAIL_MAILER=log`: le email finiscono in
`storage/logs/laravel.log` e non parte nulla verso l'esterno.

> `MAIL_FROM_ADDRESS` deve appartenere al dominio verificato. Un mittente di
> un altro dominio viene rifiutato o finisce in spam anche con credenziali valide.

Dopo ogni modifica in produzione: `php artisan config:clear`.

## 5. Alternativa: transport API HTTP

Stesso provider, stesse mail, ma via HTTPS invece che SMTP. Utile se l'hosting
blocca le porte SMTP in uscita (capita spesso) o se servono messaggi d'errore
più leggibili delle risposte SMTP.

```dotenv
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.animalamo.it
MAILGUN_SECRET=<sending api key>
MAILGUN_ENDPOINT=api.eu.mailgun.net
```

Dipendenze già installate: `symfony/mailgun-mailer` + `symfony/http-client`.
Il passaggio SMTP ⇄ API non richiede modifiche al codice.

## 5b. Demo su Forge con il dominio sandbox *(superato — vedi §0ter)*

> Dal 29 lug 2026 esiste `mg.animalamo.it` verificato: il sandbox non va più
> usato, né per il demo. Questa sezione resta come cronaca dei vincoli
> incontrati (authorized recipients, regione US, mittente non modificabile).


Configurazione da usare sul demo (`ecommerce-0tepvdzv.on-forge.com`) finché non
esiste un dominio verificato. Va nel `.env` **sul server**, mai committata:

```dotenv
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=sandboxb9ab51a50e0b4a03938857c0238c5bc9.mailgun.org
MAILGUN_SECRET=<private api key>
MAILGUN_ENDPOINT=api.mailgun.net
MAIL_FROM_ADDRESS="postmaster@sandboxb9ab51a50e0b4a03938857c0238c5bc9.mailgun.org"
MAIL_FROM_NAME="AnimalAmo"
```

Tre punti che rompono questa configurazione, tutti già verificati sul campo:

- **`api.mailgun.net`, non `api.eu.mailgun.net`.** Il sandbox sta in regione US
  anche quando i domini custom dell'account sono EU. Con l'endpoint EU la
  risposta è `{"message":"Domain not found"}`.
- **Transport API, non SMTP.** Il sandbox non ha credenziali SMTP generate
  (`/v3/domains/<sandbox>/credentials` → `total_count: 0`), quindi `MAIL_MAILER=smtp`
  non ha nulla con cui autenticarsi.
- **Authorized Recipients obbligatori.** Verso un indirizzo non in lista Mailgun
  risponde `HTTP 403 — Sandbox subdomains are for test purposes only`. Vanno
  aggiunti a mano dalla dashboard (Sending → Domains → sandbox → Authorized
  Recipients, max 5): l'endpoint API `authorized_recipients` non esiste più,
  risponde `{"error":"not found"}`. Ogni indirizzo riceve una mail di conferma
  e resta *pending* finché non clicca il link.

Dopo la modifica: `php artisan config:clear` e assicurarsi che `queue:work` sia
attivo — le mail del portale sono tutte asincrone.

Configurazione provata end-to-end il 28 lug 2026: `mail:test animalamo24@gmail.com`
→ evento `delivered` sull'API Mailgun. `animalamo24@gmail.com` è il recipient
autorizzato del sandbox (è l'indirizzo di apertura dell'account, quindi lo era
già senza configurarlo). Verso qualsiasi altro indirizzo l'invio resta 403.

> **Il sandbox non recapita più su Gmail (verificato 29 lug 2026).** Lo stesso
> `mail:test animalamo24@gmail.com` che il 28 lug risultava `delivered`, il
> giorno dopo produce `accepted` seguito da `failed` con
> `550 5.7.1 … likely unsolicited mail … blocked` (severity `espblock`). Causa:
> il sandbox invia da IP condivisi e senza SPF/DKIM sul dominio del mittente.
> Non è aggirabile lato applicazione — è l'argomento definitivo per creare il
> dominio verificato prima di qualsiasi collaudo con la cliente.
>
> Attenzione a non fidarsi dell'output di `mail:test`: stampa successo appena
> Mailgun accetta il messaggio (HTTP 200), cioè prima della consegna. La prova
> vera è `./docs/mailgun-logs.sh`, che mostra l'evento finale. I log del piano
> free vengono trattenuti solo per **1 giorno**.

### Come collaudare il demo

La cliente prova il demo usando `animalamo24@gmail.com`. Il vincolo dei recipient
vale per **ogni** indirizzo che l'applicazione tocca, non solo per quello di
login, quindi va usato lo stesso indirizzo anche in:

- registrazione utente e email dell'ordine in checkout;
- **email del destinatario del regalo Smartbox** — con un indirizzo diverso la
  `SmartboxGiftMail` fallisce con 403 e il regalo non arriva a nessuno;
- email della candidatura partner B2B, che riceve l'invito.

Il fallimento è silenzioso lato applicazione (`SendOrderPaidMails` invia con
`sendSilently()` per non far fallire il webhook Stripe): se una mail non arriva,
guardare prima la cartella spam, poi `./docs/mailgun-logs.sh`.

Il mittente resta `postmaster@sandbox….mailgun.org` e non è modificabile: sul
sandbox il from deve stare sul dominio sandbox. È il motivo principale per
passare a un dominio verificato prima di mostrare il portale a terzi.

## 6. Verifica

```bash
# usa il mailer di default (in locale: log)
php artisan mail:test destinatario@example.com

# forza il transport da provare
php artisan mail:test destinatario@example.com --mailer=smtp
php artisan mail:test destinatario@example.com --mailer=mailgun
```

Il comando stampa i parametri effettivi (password e API key mascherate),
segnala le configurazioni che "riescono" senza consegnare nulla, e restituisce
exit code ≠ 0 se l'invio fallisce — quindi è usabile anche in uno script di deploy.

Errori tipici e cosa significano:

| Messaggio | Causa |
|:--|:--|
| `530 5.7.0 Authentication required` | `MAIL_USERNAME`/`MAIL_PASSWORD` vuote |
| `535 5.7.8 Authentication failed` | credenziali sbagliate, o credenziali US su host EU |
| `Unable to send an email: 404 page not found` | `MAILGUN_DOMAIN` vuoto o inesistente sull'endpoint scelto |
| `Domain not found` / `not allowed to send` | dominio non verificato, o destinatario non autorizzato in sandbox |
| timeout in connessione | porta 587 filtrata in uscita → prova 2525 o passa al transport API |

## 7. Note operative

- **Serve un worker attivo.** Tutte le email del portale sono asincrone: il
  listener `SendOrderPaidMails` è un job, e `PartnerInvitationMail` è
  `ShouldQueue`. Con `QUEUE_CONNECTION=database` (il default di produzione),
  senza `php artisan queue:work` attivo **nessuna email parte** — restano nella
  tabella `jobs`. È il singolo punto in cui questo setup si rompe in silenzio:
  va messo sotto supervisor/systemd insieme al deploy.
- **Fallimenti non bloccanti.** `SendOrderPaidMails` cattura e logga gli errori
  di invio: chi ha pagato non vede mai un errore. Il rovescio è che una mail non
  consegnata si vede solo nei log e nella dashboard Mailgun (Sending → Logs).
- **Limiti piano.** Il piano gratuito Mailgun ha un tetto giornaliero e richiede
  carta per uscire dalla sandbox: da verificare con la cliente prima del go-live.

## 8. Passaggio in produzione

Il sandbox non è promuovibile: è un dominio usa-e-getta di Mailgun, non si
verifica e non si rinomina. Il passaggio in produzione è la creazione di un
dominio nuovo, e il sandbox si smette semplicemente di usarlo.

### 8.1 Prerequisito: un dominio che esista

Vedi §0. `animalamo.com` non è registrato, quindi o si registra, o si usa
`animalamo.it` che è già della cliente. Nulla di quanto segue è fattibile prima
di aver sciolto questo nodo.

### 8.2 Creare il sending domain

Su Mailgun, in **regione EU** (coerente col GDPR: passano indirizzi ed
eventualmente nomi di utenti italiani), creare `mg.animalamo.it`. Il
sottodominio dedicato è deliberato: la reputazione di invio si costruisce su
`mg.`, e un eventuale problema di deliverability non contamina la posta
ordinaria di `animalamo.it`.

### 8.3 Record DNS su Register.it

Quattro sono generati da Mailgun (Domain settings → DNS records) e vanno letti
di lì — il DKIM in particolare è unico per dominio. La forma è questa:

| Tipo | Host | Valore |
|:--|:--|:--|
| TXT | `mg.animalamo.it` | `v=spf1 include:mailgun.org ~all` |
| TXT | `<selector>._domainkey.mg.animalamo.it` | chiave DKIM generata da Mailgun |
| CNAME | `email.mg.animalamo.it` | `eu.mailgun.org` |
| MX | `mg.animalamo.it` | `mxa.eu.mailgun.org` e `mxb.eu.mailgun.org`, priorità 10 |
| TXT | `_dmarc.mg.animalamo.it` | `v=DMARC1; p=none; rua=mailto:…` (opzionale) |

SPF e DKIM sono gli unici obbligatori per la verifica; MX serve solo a ricevere,
il CNAME solo al tracking di aperture e click. Il DMARC non è richiesto da
Mailgun ma migliora il recapito su Gmail e Outlook — con `p=none` non rifiuta
nulla, si limita a raccogliere report.

**Attenzione al campo Host.** Molti pannelli DNS (Register.it e DigitalOcean
inclusi) appendono da soli il nome della zona. Nella zona `animalamo.it` va
scritto `mg`, non `mg.animalamo.it`, altrimenti il record finisce su
`mg.animalamo.it.animalamo.it` e la verifica non passa mai senza che sia ovvio
il perché.

Verifica da terminale prima ancora di guardare la dashboard:

```bash
dig +short TXT mg.animalamo.it
dig +short TXT <selector>._domainkey.mg.animalamo.it
```

### 8.3bis Dove pubblicare i record: Register.it o DigitalOcean

DigitalOcean può ospitare una zona, ma non può renderla autorevole: l'autorità
la stabiliscono i record NS pubblicati dal registrar. Finché `animalamo.it`
delega a `ns1/ns2.register.it`, qualunque record creato su DO è invisibile.
Quindi **una modifica su Register.it serve in ogni caso**; cambia solo quanta.

**Rotta A — record direttamente su Register.it.** Si aggiungono lì i TXT di SPF
e DKIM. Nessun coinvolgimento di DO, nessun rischio per sito e posta. Limite: le
rotazioni DKIM future ripassano da Register.it.

**Rotta B — delega del solo sottodominio a DO (consigliata).** Su Register.it si
aggiungono tre record NS per l'host `mg`:

| Tipo | Host | Valore |
|:--|:--|:--|
| NS | `mg` | `ns1.digitalocean.com` |
| NS | `mg` | `ns2.digitalocean.com` |
| NS | `mg` | `ns3.digitalocean.com` |

Poi su DigitalOcean si crea la zona `mg.animalamo.it` e ci si mettono i record
Mailgun del §8.3 — **con host relativo alla nuova zona**: SPF su `@`, DKIM su
`<selector>._domainkey`, non `<selector>._domainkey.mg.animalamo.it`. Il resto
della zona `animalamo.it` (A del sito, MX `mail.register.it`, SPF
`include:spf.webapps.net`) resta intoccato su Register.it.

Verifica della delega prima di guardare Mailgun:

```bash
dig +short NS mg.animalamo.it          # deve rispondere ns1/2/3.digitalocean.com
dig +short TXT mg.animalamo.it
```

> **Fatto il 29 lug 2026**: `mg.animalamo.it` creato su Mailgun EU (DKIM
> selector `mta`), zona `mg.animalamo.it` creata su DigitalOcean via API con
> tutti e cinque i record (SPF, DKIM, CNAME tracking, due MX). I nameserver DO
> rispondono già correttamente:
>
> ```bash
> dig +short @ns1.digitalocean.com TXT mg.animalamo.it
> ```
>
> Resta da fare **solo** la delega dei 3 NS su Register.it. Finché non c'è, la
> zona esiste ma nessun resolver la interroga e Mailgun resta `unverified`.

**Rotta C — zona intera su DO.** Sconsigliata qui: prima di cambiare gli NS al
registrar vanno ricreati su DO **tutti** i record esistenti (A `195.110.124.133`,
`www`, MX `mail.register.it` priorità 10, TXT `v=spf1 include:spf.webapps.net ~all`),
altrimenti sito ed email della cliente cadono durante la propagazione.

> **DKIM a 2048 bit** supera i 255 caratteri di una singola stringa TXT: DO lo
> gestisce, alcuni pannelli di registrar no. Se il pannello rifiuta il valore,
> ricreare il dominio su Mailgun con chiave a 1024 bit invece di spezzare la
> stringa a mano.

### 8.4 Configurazione applicativa

```dotenv
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.animalamo.it
MAILGUN_SECRET=<sending key EU>
MAILGUN_ENDPOINT=api.eu.mailgun.net
MAIL_FROM_ADDRESS="no-reply@mg.animalamo.it"
MAIL_FROM_NAME="AnimalAmo"
```

Poi `php artisan config:clear`. Le sending key sono valide su entrambe le
regioni (verificato: `HTTP 200` sia su `api.mailgun.net` che su
`api.eu.mailgun.net`), quindi non serve rigenerarle passando da US a EU — cambia
solo `MAILGUN_ENDPOINT`.

### 8.5 Prima del go-live

- Dominio **verde** su Mailgun (tutti i record verificati).
- Piano a pagamento attivo, o quantomeno carta registrata: il tier gratuito ha
  un tetto giornaliero che un portale con ordini reali supera in fretta.
- `queue:work` sotto supervisor — vedi §7, è il punto in cui il setup si rompe
  in silenzio.
- `mail:test` verso un indirizzo **esterno** all'account (Gmail, Outlook e un
  dominio aziendale): fuori dal sandbox non ci sono più recipient autorizzati,
  ed è l'unico modo di accorgersi che si finisce in spam.
- Ruotare le chiavi usate in sviluppo e tenere in produzione una sending key
  dedicata.

## 9. Recapito: DMARC, Reply-To e webhook *(18 set 2026)*

Verifica nata dal reclamo della cliente: «alcuni partner non ricevono l'email di
iscrizione, ad altri arriva in spam». Sul lato Mailgun **non c'era niente di
rotto**: dominio `active`, SPF e DKIM `valid`, liste di soppressione
(bounce/complaint/unsubscribe) **vuote**, 0 `rejected`. Le cause stanno altrove.

### 9.1 Cosa dicevano i log

| Evento | Destinatario | Esito |
|:--|:--|:--|
| 17/09 19:01 `failed` | `info@…​.comm` | `No MX for …comm … no such host` |
| 17/09 19:01 `delivered` | `info@…​.com` | `250` — stesso destinatario, indirizzo corretto |
| 14/09 `delivered` | struttura su Aruba | `250`, consegna al MX in 0,1s |

Due lezioni: un refuso nel TLD passava la validazione (`'email'` controlla solo
la sintassi), e la consegna al MX non dice nulla su dove il messaggio finisca
dentro la casella. Per il resto, vedi anche `./docs/mailgun-logs.sh`: la
ritenzione è di pochi giorni, quindi i log **non** sono un archivio.

### 9.2 DMARC (la causa più probabile dello spam)

`_dmarc.animalamo.it` e `_dmarc.mg.animalamo.it` erano **entrambi assenti**.
Mailgun non lo elenca fra i suoi record e il dominio resta `active` lo stesso:
è l'unico pezzo che nessuno segnala come mancante. Con dominio nuovo, volume
bassissimo e IP condivisi, per Gmail/Aruba/Libero non resta alcun segnale di
reputazione.

Record da pubblicare su **DigitalOcean**, zona `mg.animalamo.it`:

```
TXT  _dmarc   v=DMARC1; p=none; rua=mailto:dmarc@animalamo.it; adkim=r; aspf=r; pct=100
```

`p=none` è volutamente in sola osservazione: si raccolgono i report per qualche
settimana e solo dopo si passa a `quarantine`. Irrigidire subito, con un dominio
senza storico, rischia di far sparire mail legittime.

Lo stesso record va poi su **Register.it** per `animalamo.it` (host `_dmarc`),
che è quello che i filtri interrogano per primo ora che il `From` è
`no-reply@animalamo.it`. Controllo: `./docs/mailgun-verify.sh`, sezione DMARC.

### 9.3 From e Reply-To

- `MAIL_FROM_ADDRESS="no-reply@animalamo.it"` — il dominio che il destinatario
  conosce. L'allineamento DMARC regge in modalità *relaxed*: DKIM firma
  `d=mg.animalamo.it`, sottodominio dello stesso dominio organizzativo, e il
  Return-Path resta su `mg.animalamo.it`, coperto da SPF. **Non** passare
  `adkim=s`/`aspf=s` senza rifare i conti.
- `MAIL_REPLY_TO_ADDRESS` — `mail.reply_to` è nativo in Laravel: lo applica
  `MailManager` come `alwaysReplyTo`, senza codice. Attenzione, un mailable che
  dichiara il proprio Reply-To lo *aggiunge* invece di sostituirlo: per questo
  `ContactMessageMail` usa il callback `using:` dell'Envelope.

### 9.4 Webhook: l'esito reale entra nel progetto

`POST /webhooks/mailgun` (firma HMAC + finestra di 15 minuti sul timestamp)
scrive su `mail_deliveries`, una riga per messaggio+destinatario aperta da
`RecordMailDelivery` quando la mail parte.

Configurazione su Mailgun → *Webhooks*, dominio `mg.animalamo.it`, eventi
`delivered`, `permanent_fail`, `temporary_fail`, `complained` verso
`https://animalamo.it/webhooks/mailgun`; la *HTTP webhook signing key* (una per
account, **diversa** dalla sending key) va in `MAILGUN_WEBHOOK_SIGNING_KEY`.
Senza chiave ogni webhook viene rifiutato con 403: è il default sicuro.

Lettura: `php artisan mail:deliveries --failed --days=30`, che è anche la
risposta alla domanda «a chi non è arrivata?» finché non esiste un'area admin.

### 9.5 Cosa resta fuori dal codice

- Pubblicare i due record DMARC (§9.2).
- `.env` di produzione: `MAIL_FROM_ADDRESS`, `MAIL_REPLY_TO_ADDRESS`,
  `MAILGUN_WEBHOOK_SIGNING_KEY`, poi `php artisan config:clear`.
- Verificare che `queue:work` giri davvero. **Non** tutte le mail sono in coda,
  come dicono tre documenti di questo repo: lo sono solo `PartnerInvitationMail`
  e `ContactMessageMail`. `ResetPasswordMail`, `OrderConfirmationMail` e
  `SmartboxGiftMail` partono dentro la richiesta HTTP. La distinzione è quella
  che serve quando la cliente chiede «perché a lui sì e a lei no»: un reset
  password consegnato dimostra che Mailgun funziona, non che il worker giri.
  E la candidatura passa a `INVITED` appena il job entra in coda — worker fermo
  significa stato "invitato" senza nessuna mail partita.


## 10. Correzioni dopo l'audit del 18 set 2026

Verifiche fatte con l'API Mailgun e sul messaggio reale, che smentiscono cose
scritte altrove (qui e nel `.env` di produzione):

- **La ritenzione degli eventi è di ~4 giorni, non "qualche giorno" generico.**
  Una query con `begin` a 35 giorni indietro torna 16 eventi, il più vecchio del
  14/09; le statistiche contano invii dell'8, 9 e 10 settembre che non hanno più
  nessun evento. Quando una segnalazione arriva dopo una settimana, la prova non
  esiste più: è il motivo per cui serve `mail_deliveries`.
- **Mailgun accetta un `From` fuori dal sending domain.** Provato in test mode
  (`o:testmode=yes`, nessuna consegna): `from=no-reply@animalamo.it` su
  `mg.animalamo.it` risponde `200 Queued`. Il warning di `MailTestCommand`
  («Mailgun accetta solo mittenti del dominio verificato») è sbagliato e va
  riscritto: il vincolo non è di Mailgun, è di allineamento DMARC.
- **Il sandbox US è ancora attivo e la stessa chiave lo apre.** `GET /v3/domains`
  sull'endpoint US elenca `sandbox…9.mailgun.org`; sullo stesso endpoint
  `mg.animalamo.it` risponde 404. La chiave non è vincolata a una regione: è il
  default `api.eu.mailgun.net` in `config/services.php` a tenere gli invii in EU.
  Il commento in testa al blocco mail del `.env` di produzione descrive ancora il
  sandbox e va riscritto.
- **Nessuna credenziale SMTP esiste sul dominio** (`GET /credentials` → 0):
  `MAIL_MAILER=smtp` non è una via di ritorno, fallirebbe in autenticazione.
  L'unico fallback è `MAIL_MAILER=log`.
- **Nessuna route in entrata** (`GET /v3/routes` → 0) mentre gli MX di
  `mg.animalamo.it` puntano a Mailgun: la posta inviata a `no-reply@…` viene
  accettata e buttata via senza errore. Chi risponde all'invito non è letto da
  nessuno — un altro motivo per il `Reply-To`.
- **`/v4/address/validate` è disponibile** su questo account: su
  `babaresidences.comm` avrebbe risposto `undeliverable`. Resta un'alternativa a
  `email:rfc,dns` se i refusi si ripetono, non una sostituzione da fare ora.
- **Ordine obbligato per i webhook**: prima il deploy del codice (senza rotta è
  un 404), poi `MAILGUN_WEBHOOK_SIGNING_KEY` + `config:cache` + `queue:restart`,
  e **solo dopo** l'attivazione su `app.eu.mailgun.com` (regione EU: sulla
  dashboard US si vede solo il sandbox). Attivarli prima significa 403 o 404 in
  serie, che Mailgun ritenta per ore e che possono far disattivare l'endpoint.
- **Dopo ogni modifica del `.env` serve `config:cache` e `queue:restart`**, non
  il `config:clear` ripetuto in questi documenti: il pannello Forge scrive il
  file ma non tocca i daemon, e il worker continua a spedire con la vecchia
  configurazione finché non viene riavviato.
