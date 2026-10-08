# Runbook — stanze dentro la struttura e accorpamento del Casale

**Cosa cambia (08/10/2026):** una struttura può avere più **stanze** (`rooms`: nome, descrizione, prezzo,
ospiti, animali, unità, foto, servizi). Il cliente sceglie la stanza dalla scheda, il checkout blocca la
stanza e l'**occupazione è applicata ai soli partner a incasso online**. Le strutture create «una per
camera» (caso reale: «Il Casale Sotto le Stelle - Monia / - Dona / - Stella») si accorpano con
`catalog:merge-structures`.

Branch: `feature/structure-rooms`. Spec: `docs/specs/2026-10-08-stanze-struttura-design.md`.

---

## 1. Deploy (ordine)

```bash
# 0. Backup del database, sempre prima delle migrazioni
mysqldump … > backup-prima-stanze.sql

# 1. Codice e dipendenze
git pull
composer install --no-dev --optimize-autoloader

# 2. Migrazioni: tabella rooms, order_items.room_id, merged_into_* su structures e structure_drafts
php artisan migrate --force

# 3. Asset: servono, ci sono classi Tailwind nuove (public/build non è versionato)
npm ci && npm run build

# 4. Cache
php artisan optimize:clear        # e, se in produzione si usano, config:cache / route:cache / view:cache
```

Le strutture esistenti restano senza righe `rooms` finché non vengono ripubblicate o accorpate: il
comportamento per il cliente non cambia fino ad allora.

---

## 2. Accorpamento del Casale

Ruoli: **target** = la struttura che resta (Monia), **sources** = quelle che diventano stanze (Dona, Stella).

### 2.1 Trovare gli id

```bash
php artisan tinker --execute="App\Models\Structure\Structure::withHidden()->where('name->it','like','%Casale Sotto le Stelle%')->get(['id','user_id','type','structure_draft_id','slug','price_from_cents'])->each(fn(\$s)=>dump(\$s->toArray()));"
```

Devono avere lo stesso `user_id` (stesso partner) e lo stesso `type`; altrimenti il comando rifiuta e non scrive.

### 2.2 Anteprima

```bash
php artisan catalog:merge-structures <MONIA_ID> <DONA_ID> <STELLA_ID> --dry-run
```

Nella tabella controlla:
- **nomi** delle stanze (Monia, Dona, Stella) e che la riga `#<target> (target)` ci sia;
- **prezzi** e **unità** di ogni stanza;
- **numero di foto** per stanza;
- **recensioni** e **righe ordine** che si spostano o si rilegano: la colonna «Righe ordine» della riga
  del target indica quante prenotazioni del target verranno collegate alla sua stanza;
- le righe **warning**: in particolare «righe ordine del target restano senza stanza» (vedi §3);
- che il partner sia a incasso online con Stripe operativo (altrimenti l'occupazione non viene applicata).

Il dry run non scrive niente. Se qualcosa non torna, fermarsi qui.

### 2.3 Esecuzione reale

```bash
php artisan catalog:merge-structures <MONIA_ID> <DONA_ID> <STELLA_ID>
```

Non c'è conferma interattiva: l'anteprima è la rete di sicurezza.

### 2.4 Dopo l'esecuzione

1. Dal **pannello admin** rinomina il target in «Il Casale Sotto le Stelle» e correggi la descrizione se
   parla solo di Monia. Attenzione: «Modifica» da admin sovrascrive `price_cents`/`price_from_cents` con
   un prezzo unico; il nuovo slug (`nome-idbozza`) si applica alla successiva ripubblicazione, e dopo
   di essa il vecchio URL del target dà 404 (comportamento preesistente, nessuna storia degli slug).
2. Verifica:
   - la scheda del target mostra **3 stanze** e il selettore stanza funziona (`?camera=`);
   - gli URL vecchi di Dona e Stella rispondono **301** al target;
   - «I miei servizi» del partner elenca **un solo** servizio;
   - una prenotazione di prova su ogni stanza rispetta prezzo e disponibilità.
3. `php artisan optimize:clear` se rotte e viste sono in cache.

---

## 3. Limiti noti

- **Strutture legacy con più tipologie di camera:** se una struttura senza stanze ha più righe camera e
  viene ripubblicata (o è target di un accorpamento), le prenotazioni storiche restano **senza stanza**
  (`room_id` NULL) e **non contano per l'occupazione**. L'anteprima ne segnala il numero: controllare a
  mano quelle date. Con una sola riga camera, invece, vengono collegate automaticamente.
- **Il 301 perde la query string** e non preseleziona la stanza corrispondente (la mappatura
  sorgente→stanza non è memorizzata).
- **Le chiusure restano per struttura**, non per stanza.
- **Una struttura sorgente accorpata si può riattivare dall'admin: non farlo.** Duplicherebbe l'offerta
  e romperebbe il 301.
- **Ripubblicare il target manda online anche le modifiche in bozza non ancora pubblicate.** Prima di
  ripubblicare, controllare la bozza del partner.
- L'occupazione è applicata solo ai partner **Online**; gli OnSite non sono bloccati dalla disponibilità.
