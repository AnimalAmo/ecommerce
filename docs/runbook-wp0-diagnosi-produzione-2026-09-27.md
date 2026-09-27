# WP0 — Diagnosi in produzione e recupero delle schede ferme

**Perché:** la cliente ha autorizzato la verifica il 27/09/2026 («procedi pure con la verifica sul server e con
il recupero delle schede rimaste bloccate») e **aspetta la conferma prima di dire ai partner che è risolto**.
Alcuni partner hanno collegato Stripe e la loro scheda non è online.

**Dove:** server Forge, `/home/forge/animalamo.it`. Non ho accesso SSH: i blocchi 1 e 2 vanno eseguiti da chi
ce l'ha, e mi serve l'output così com'è.

**Cosa fa:** i blocchi 1 e 2 sono **in sola lettura**, non cambiano niente. Il blocco 3 scrive, e va eseguito
solo dopo aver guardato l'output dei primi due.

---

## Blocco 1 — Stato dell'installazione (sola lettura)

```bash
cd /home/forge/animalamo.it

echo "=== 1. cron schedule:run ==="
crontab -l 2>/dev/null | grep -c "schedule:run" ; crontab -l 2>/dev/null | grep "artisan"

echo "=== 2. coda ==="
php artisan tinker --execute="echo config('queue.default');"
ps aux | grep -c "[q]ueue:work"

echo "=== 3. moderazione preventiva ==="
php artisan tinker --execute="echo (int) config('admin.moderation');"

echo "=== 4. segreti webhook Stripe (attesi: 2) ==="
php artisan tinker --execute="echo count(array_filter(explode(',', (string) config('payment.stripe.webhook_secret'))));"

echo "=== 5. rotta webhook registrata ==="
php artisan route:list --path=webhooks

echo "=== 6. bozze ferme (NON scrive niente) ==="
php artisan animalamo:stuck-drafts

echo "=== 7. pubblicazione differita: quante ne trova adesso ==="
php artisan animalamo:publish-awaiting-drafts

echo "=== 8. bonifici: righe fallite in attesa di un retry manuale ==="
php artisan tinker --execute="echo \App\Models\Order\OrderPayout::whereIn('status',['Pending','Failed'])->count();"
```

## Blocco 2 — Query (sola lettura)

```bash
cd /home/forge/animalamo.it && php artisan db < /dev/stdin <<'SQL'
-- A. FM-1: bozze arrivate in fondo al wizard e mai finite a catalogo
SELECT sd.id, sd.user_id, sd.status, sd.current_step, sd.publish_requested_at, sd.updated_at
FROM structure_drafts sd
LEFT JOIN structures s ON s.structure_draft_id = sd.id
LEFT JOIN events e ON e.structure_draft_id = sd.id
LEFT JOIN smartbox_packages b ON b.structure_draft_id = sd.id
WHERE sd.publish_requested_at IS NULL AND s.id IS NULL AND e.id IS NULL AND b.id IS NULL
  AND sd.current_step >= 9 AND sd.user_id IS NOT NULL;

-- B. FM-5: righe pubblicate ma invisibili perché in attesa di approvazione
SELECT 'structures' t, approval_status, COUNT(*) n FROM structures GROUP BY approval_status
UNION ALL SELECT 'events', approval_status, COUNT(*) FROM events GROUP BY approval_status
UNION ALL SELECT 'smartbox_packages', approval_status, COUNT(*) FROM smartbox_packages GROUP BY approval_status;

-- C. FM-6: strutture pubblicate senza regione (invisibili su ogni pagina regione)
SELECT id, slug, user_id, structure_draft_id FROM structures
WHERE region_id IS NULL AND structure_draft_id IS NOT NULL;

-- D. FM-6: province scritte nel wizard che non esistono a catalogo
SELECT DISTINCT province FROM structure_drafts
WHERE province IS NOT NULL AND province NOT IN (SELECT short_name FROM provinces);

-- E. Smartbox di partner senza pagamento online (le ritira WP9)
SELECT b.id, b.slug, b.user_id, pp.online_payment, pp.stripe_charges_enabled, pp.stripe_payouts_enabled
FROM smartbox_packages b
JOIN partner_profiles pp ON pp.user_id = b.user_id
WHERE pp.online_payment = 0 OR pp.stripe_payouts_enabled = 0;
SQL
```

---

## Come si legge l'output

| Punto | Risultato | Significato | Cosa segue |
|---|---|---|---|
| 1 | `0` | il cron non è installato | **cade tutto**: pubblicazione differita **e** `payouts:release`. Da installare subito: `* * * * * cd /home/forge/animalamo.it && php artisan schedule:run >> /dev/null 2>&1` |
| 2 | `database` e nessun `queue:work` | le email e parte della pubblicazione non partono mai | worker da avviare (Forge → Daemons) |
| 3 | `1` | moderazione preventiva accesa | ogni riga nuova nasce `pending` e il partner vede "pubblicato" mentre il sito non la mostra. **Lo stato è appiccicoso**: spegnere la moderazione non libera le righe già in attesa, `approved` è un default di colonna |
| 4 | `1` | manca un segreto | con Connect servono due endpoint sullo stesso URL: ogni `account.updated` fallisce la firma, Stripe ritenta e poi **disabilita l'endpoint**, che è lo stesso dei pagamenti |
| 5 | nessuna rotta | 404 muto sui webhook | manca la riga in `payment_gateways` o è sporca la cache di configurazione |
| 6 | N > 0 gruppo 1 | bozze senza `publish_requested_at` | è la causa più probabile del reclamo: le recupera il blocco 3 |
| 7 | N > 0 | il comando trova lavoro da fare *adesso* | se il cron gira, non dovrebbe trovare nulla: conferma il punto 1 |
| 8 | N > 0 | bonifici in sospeso | `payouts:retry` **non è nello schedule** (`routes/console.php`): una riga che esaurisce i tentativi non la riprende nessuno. Da schedulare in WP1 |
| A | righe | le bozze del reclamo, con nome del partner | blocco 3 |
| B | `pending` > 0 | righe invisibili anche se pubblicate | vanno approvate dal pannello admin, una per una o con una migrazione dati |
| C/D | righe | schede pubblicate e irraggiungibili | la provincia va corretta a mano sulla bozza e la scheda ri-pubblicata; poi la regola `exists` nel wizard (WP1) impedisce che ricapiti |
| E | righe | smartbox non acquistabili in vetrina | censimento per WP9 |

---

## Blocco 3 — Recupero (scrive)

Solo dopo aver letto l'output del blocco 1 punto 6, che elenca **quali** bozze toccherebbe.

```bash
cd /home/forge/animalamo.it
php artisan animalamo:stuck-drafts --fix
php artisan animalamo:publish-awaiting-drafts
php artisan animalamo:connect-sync
php artisan animalamo:stuck-drafts   # deve tornare vuoto, o dire una causa diversa
```

`--fix` scrive `publish_requested_at` **solo** sulle bozze del primo gruppo, quelle arrivate in fondo al
wizard: una bozza lasciata a metà non viene svegliata. Il comando dopo la pubblica; `connect-sync` rilegge da
Stripe i flag dei partner, nel caso un `account.updated` sia andato perso.

---

## Cosa dire alla cliente, e quando

Non prima di aver riletto l'elenco. Due cause restano possibili anche dopo il recupero, e si vedono solo dalle
query C e D: una scheda può risultare **pubblicata e comunque invisibile** se la provincia scritta nel wizard
non è stata riconosciuta, oppure se la moderazione preventiva è accesa. Entrambe vanno chiuse caso per caso
prima di scrivere ai partner che è risolto.

---

# Appendice — «all'ultimo passo il sistema mi butta fuori cancellando i dati»

Segnalazione del **27/09/2026, ore 14:05**, da *Agriturismo Metina* (Monica Anselmetti, Montepulciano SI,
`info@metina.it`), inoltrata dalla cliente: «ho completato tutte le informazioni richieste, ma all'ultimo
passo il sistema mi "butta fuori" cancellando i dati. Al 5° tentativo ho rinunciato.»

## Cosa è già certo, senza toccare il server

**Una bozza a metà è raggiungibile solo dalla sessione, e da nessun'altra parte.**

- Il wizard tiene la bozza in `session('structure_draft_id')` — `InteractsWithStructureDraft::draft()`.
  Se la sessione non ce l'ha più, `draft()` **ne crea una nuova vuota**.
- "I miei servizi" elenca solo le bozze `completed` **oppure** con `publish_requested_at` valorizzato —
  `StructureDraft::scopeListableFor()`, `app/Models/Structure/StructureDraft.php:151-158`. Una bozza in
  corso non ha né l'uno né l'altro: **non compare**.
- L'unico punto che rimette la sessione su una bozza è `PartnerMyServices::edit()`
  (`app/Livewire/Partner/MyServices/PartnerMyServices.php:37`), raggiungibile solo da quella lista.

Quindi: **persa la sessione, il lavoro è irraggiungibile dall'interfaccia** anche se le righe sono tutte a
database. Il partner riapre "Crea servizio", ne nasce una nuova vuota, e dal suo punto di vista il sistema
ha cancellato i dati. È anche il motivo per cui è successo **cinque volte su cinque**: non c'è modo di
riprendere, quindi ogni tentativo riparte da zero.

Nessun percorso cancella davvero una bozza in corso: le uniche `delete()` sono l'eliminazione esplicita da
"I miei servizi" (`DeleteServiceModal`), quella del pannello admin e la pulizia del catalogo mock.

## Cosa la butta fuori — da stabilire sul server

In ordine di probabilità:

1. **La sessione scade.** `SESSION_LIFETIME` è a **120 minuti di inattività** (`config/session.php:35`,
   `SESSION_DRIVER=database`). Undici step, con le foto da caricare e la licenza da cercare, superano due ore
   di inattività senza sforzo — e succede **all'ultimo passo** proprio perché è quello più lontano
   dall'inizio. Alla scadenza la richiesta Livewire prende un **419**, la pagina si ricarica e finisce sul
   login: «mi butta fuori», letteralmente.
2. **Il caricamento delle foto supera i limiti PHP.** Se il POST eccede `post_max_size`, PHP scarta
   l'intera richiesta: Laravel non vede più nemmeno il token CSRF e risponde **419**, con lo stesso effetto.
3. Un 500 all'ultimo step. Meno probabile: `DraftCompleter` cattura già il caso del partner non ancora
   pagabile e `completeDraft()` quello della bozza incompleta (toast, si resta sullo step).

```bash
cd /home/forge/animalamo.it

echo "=== durata sessione e driver ==="
php artisan tinker --execute="echo config('session.lifetime'),' min / ',config('session.driver');"

echo "=== limiti di upload ==="
php -r 'echo "post_max_size=",ini_get("post_max_size")," upload_max_filesize=",ini_get("upload_max_filesize")," max_file_uploads=",ini_get("max_file_uploads"),PHP_EOL;'
grep -r "client_max_body_size" /etc/nginx/ 2>/dev/null | head

echo "=== 419 e 500 recenti ==="
grep -c "419\|TokenMismatch" storage/logs/laravel*.log 2>/dev/null
tail -200 storage/logs/laravel.log | grep -iE "production.ERROR" | tail -20
```

## La bozza di Metina: c'è ancora?

```bash
cd /home/forge/animalamo.it && php artisan db < /dev/stdin <<'SQL'
SELECT sd.id, sd.user_id, u.email, sd.service_category, sd.type, sd.status,
       sd.current_step, sd.publish_requested_at, sd.created_at, sd.updated_at,
       JSON_UNQUOTE(JSON_EXTRACT(sd.name, '$.it')) AS nome
FROM structure_drafts sd
JOIN users u ON u.id = sd.user_id
WHERE u.email LIKE '%metina%' OR JSON_UNQUOTE(JSON_EXTRACT(sd.name, '$.it')) LIKE '%etina%'
ORDER BY sd.id DESC;
SQL
```

Se le righe ci sono — ed è l'ipotesi forte — **il lavoro non è perduto**, è solo orfano. Si recupera
esattamente come le schede ferme del blocco 3:

```bash
php artisan animalamo:stuck-drafts
```

Le bozze del **primo gruppo** («senza segnale: pronte, mai pubblicate, invisibili anche al partner») sono
queste. `--fix` scrive loro `publish_requested_at`, e da quel momento **compaiono in "I miei servizi"**, dove
il partner può riprenderle con "Modifica". Se la bozza di Metina è arrivata in fondo, è lì dentro.

## Il difetto da chiudere, e non è il 419

Il 419 è l'innesco; il difetto è che **una bozza in corso non si può riprendere**. Finché resta così,
qualunque intoppo — sessione scaduta, browser chiuso, un altro dispositivo, un upload rifiutato — costa al
partner tutto il lavoro fatto. La correzione è far comparire le bozze in corso in "I miei servizi", con il
loro stato, invece di mostrarle solo quando sono finite. È mezza giornata e va davanti al resto della
tranche C: ogni giorno che resta così è un partner che rinuncia al quinto tentativo, come ha fatto Metina.
