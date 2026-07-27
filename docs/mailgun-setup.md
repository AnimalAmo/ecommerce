# Mailgun — configurazione invio email

Provider SMTP scelto dalla cliente (lug 2026) per tutte le email del portale:
conferma ordine, regalo Smartbox, invito partner B2B.

Lato codice è già tutto pronto: restano solo le credenziali e i record DNS.

---

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

- **Invio sincrono.** `QUEUE_CONNECTION` è `database` in produzione ma il
  listener `SendOrderPaidMails` gira come job: serve un worker attivo
  (`php artisan queue:work`) o le mail post-pagamento restano in coda.
  L'invito partner (`SendPartnerInvitation`) parte invece in-process e aggiunge
  la latenza SMTP alla richiesta HTTP.
- **Fallimenti non bloccanti.** `SendOrderPaidMails` cattura e logga gli errori
  di invio: chi ha pagato non vede mai un errore. Il rovescio è che una mail non
  consegnata si vede solo nei log e nella dashboard Mailgun (Sending → Logs).
- **Limiti piano.** Il piano gratuito Mailgun ha un tetto giornaliero e richiede
  carta per uscire dalla sandbox: da verificare con la cliente prima del go-live.
