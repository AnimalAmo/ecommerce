# Mailgun — configurazione invio email

Provider SMTP scelto dalla cliente (lug 2026) per tutte le email del portale:
conferma ordine, regalo Smartbox, invito partner B2B.

Lato codice è già tutto pronto: restano solo le credenziali e i record DNS.

---

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

## 5b. Demo su Forge con il dominio sandbox

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
autorizzato del sandbox. Verso qualsiasi altro indirizzo l'invio resta 403.

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
