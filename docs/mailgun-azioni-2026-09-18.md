# Recapito email — cosa resta da fare fuori dal codice

*18 settembre 2026. Contesto e diagnosi completa: `docs/mailgun-setup.md` §9.*

## 1. DMARC (priorità: è il segnale che manca ai filtri severi)

Oggi `animalamo.it` e `mg.animalamo.it` non hanno **nessun** record DMARC. Confermato
sia dal DNS (`dig`) sia dal lato ricevente: nell'`Authentication-Results` di una mail
del portale arrivata a Gmail l'8/09 compaiono `spf=pass` e due `dkim=pass`, e la riga
`dmarc=` non c'è proprio — Google non lo valuta perché il record non esiste.

**Il DMARC si cerca sul dominio che compare nel `From:`**, non su quello della busta.
Da qui discende tutto l'ordine delle operazioni:

| From | Record consultato | Zona |
|:--|:--|:--|
| `no-reply@mg.animalamo.it` (produzione oggi) | `_dmarc.mg.animalamo.it`, con fallback all'apex | DigitalOcean |
| `no-reply@animalamo.it` (dopo il deploy del working tree) | **solo** `_dmarc.animalamo.it` | Register.it |

### 1.1 Subito, su DigitalOcean (zona `mg.animalamo.it`)

| Tipo | Host | Valore |
|:--|:--|:--|
| TXT | `_dmarc` | `v=DMARC1; p=none; adkim=r; aspf=r; pct=100` |

Efficace da subito, perché la produzione oggi spedisce con il `From` sul sottodominio.

**Sul `rua`**: un indirizzo di report su un dominio *diverso* da quello che pubblica il
record va autorizzato da chi riceve i report. Con `rua=mailto:dmarc@animalamo.it` dentro
`_dmarc.mg.animalamo.it` servirebbe anche, su Register.it, un
`TXT mg.animalamo.it._report._dmarc.animalamo.it` con valore `"v=DMARC1"` — e si perde
il vantaggio di fare tutto da soli. Alternative: nessun `rua` (record valido lo stesso),
oppure `rua=mailto:dmarc@mg.animalamo.it` con una Route Mailgun che inoltra a
`animalamo24@gmail.com` (gli MX di `mg` sono già su Mailgun), oppure un servizio di
report che pubblica l'autorizzazione per conto suo.

### 1.2 Poi, su Register.it (zona `animalamo.it`)

Stesso valore, host `_dmarc`. Copre anche i sottodomini (`sp` eredita da `p`) ed è il
record che i filtri cercano quando il `From` porta il dominio del marchio. `p=none` è
sola osservazione: non tocca nemmeno la posta delle caselle Register.it.

### 1.3 Ordine, e perché

1. Record su DigitalOcean → la produzione di oggi ha un DMARC valutabile.
2. Record su Register.it → il dominio principale ne ha uno.
3. **Solo dopo il punto 2**, deploy del cambio `MAIL_FROM_ADDRESS` su `no-reply@animalamo.it`.
   Invertire 2 e 3 sposta il mittente su un dominio senza DMARC e rende inutile il
   record appena pubblicato.
4. Dopo qualche settimana di report, valutare `p=quarantine`.

## 2. `.env` di produzione (Forge → Site → Environment)

```
MAIL_REPLY_TO_ADDRESS="animalamo24@gmail.com"
MAILGUN_WEBHOOK_SIGNING_KEY=<Mailgun → Webhooks → HTTP webhook signing key>

# solo DOPO che _dmarc.animalamo.it è pubblicato su Register.it — vedi §1.3
MAIL_FROM_ADDRESS="no-reply@animalamo.it"
```

**La sending key attuale va rigenerata**: è stata esposta fuori dal server. Mailgun →
Sending → Domain settings → Sending keys, poi `MAILGUN_SECRET` aggiornata su Forge.

Poi `php artisan config:clear`. La signing key è **una per account** ed è diversa
dalla sending key già presente in `MAILGUN_SECRET`.

## 3. Webhook su Mailgun — l'ordine conta

Oggi non ce n'è nessuno (`GET /v3/domains/mg.animalamo.it/webhooks` → vuoto), quindi
è un'aggiunta, non una modifica. Ma i passi vanno in quest'ordine, o Mailgun ritenta
per ore e può disattivare l'endpoint da solo:

1. **Deploy del codice** (rotta, controller, modello, listener, migrazione) e
   `php artisan migrate --force`. Prima del deploy `POST /webhooks/mailgun` è un 404,
   che per Mailgun è un fallimento come il 403.
2. **`MAILGUN_WEBHOOK_SIGNING_KEY`** su Forge, poi `php artisan config:cache` e
   `php artisan queue:restart`. Senza chiave il controller risponde 403 a tutto:
   è il default sicuro, ma è anche un endpoint che rifiuta.
3. **Solo adesso** attivare i webhook su **app.eu.mailgun.com** (regione EU — sulla
   dashboard US si vede soltanto il vecchio sandbox), dominio `mg.animalamo.it`, URL
   `https://animalamo.it/webhooks/mailgun`, eventi `delivered`, `permanent_fail`,
   `temporary_fail`, `complained`.

Da quel momento `php artisan mail:deliveries --failed --days=30` dice a chi non è
arrivata una mail e perché. Serve davvero: la ritenzione degli eventi Mailgun è di
**circa 4 giorni** (misurata: una query a 35 giorni indietro torna solo eventi dal
14/09 in poi), quindi una segnalazione che arriva dopo una settimana oggi non è più
verificabile da nessuna parte.

## 4. Da controllare sulla dashboard Forge

1. **Daemons**: deve esistere un processo `php artisan queue:work` **running**
   sul sito animalamo.it. In coda ci sono l'**invito partner** e il messaggio di
   **Contattaci**; reset password, conferma ordine e regalo Smartbox partono invece
   dentro la richiesta HTTP. Quindi: worker fermo = inviti mai partiti, mentre i
   reset password continuano ad arrivare — ed è esattamente il «a lui sì e a lei no»
   della cliente. La candidatura passa comunque a "invitata".
   Annotare da quando il daemon è in esecuzione: se è ripartito di recente, le mail
   accodate prima possono essere rimaste indietro.
2. **Queue → Failed jobs**: quante righe, e di che job. `SendQueuedMailable` fra
   i falliti = mail mai partite, con destinatario e ora nel payload.
3. **Environment**: che `MAIL_MAILER=mailgun`, `MAILGUN_DOMAIN=mg.animalamo.it`
   e `MAILGUN_ENDPOINT=api.eu.mailgun.net` (endpoint US su dominio EU = mail che
   non partono), e che `QUEUE_CONNECTION` non sia `sync`.

4. **`QUEUE_CONNECTION`**: se la variabile manca del tutto, il default del codice è
   `database` — cioè una coda che senza daemon non spedisce niente e non dà errore.

Da riga di comando, se più comodo:

```bash
php artisan queue:failed
```

## 5. Nota per la cliente

Due casi visti nei log valgono una risposta esplicita:

- un partner aveva digitato l'indirizzo con un refuso nel dominio: l'email non
  poteva arrivare a nessuna casella. Da oggi il form rifiuta un dominio che non
  esiste invece di accettarlo in silenzio;
- chi compila "Lavora con noi" mentre è già loggato con un account AnimalAmo
  riceve l'invito **sull'email dell'account**, non su quella scritta nel form.
  È una scelta di progetto (l'email decide quale utente diventa partner), ma va
  detta: dall'esterno sembra una mail persa.
