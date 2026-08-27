# Pagina Privacy Policy (it + en)

Data: 2026-08-27

Seguito di `2026-08-26-legal-pages-design.md`, che elencava la privacy policy
fra le cose fuori scope perché il testo non c'era. Ora c'è.

## Problema

Il cliente ha fornito l'informativa privacy in due file markdown,
`docs/privacy-it.md` e `docs/privacy-en.md`: 18 capitoli, ~19.000 caratteri per
lingua, nessuna immagine, nessun link. Tre punti del sito promettono una pagina
privacy che non esiste — `site-footer`, `partner-footer` e `footer-minimal`,
tutti con `href="#"`.

L'infrastruttura per servirla esiste già: model `Page`, componente `LegalPage`,
`PageSeeder` che legge HTML versionato da `database/seeders/content/`, blocco
CSS `.legal-content`. Aggiungere la pagina costa contenuto e cablaggio, non
architettura.

## Decisioni prese

1. **Una sola pagina per utenti e strutture.** Il documento tratta entrambi
   (il capitolo 2 ha una sezione dedicata ai dati di strutture e operatori),
   quindi i tre footer puntano tutti allo stesso URL.
2. **URL `privacy-policy` in entrambe le lingue.** È la dicitura che i footer
   già usano ed è italiano d'uso corrente. Lo slug del contenuto
   (`Page::PRIVACY`) resta distinto dall'URL, come per i termini.
3. **I markdown sorgente vanno in git.** A differenza dei `.docx` dei termini,
   lasciati untracked il 26/08: sono testo, il diff di una revisione si legge,
   e da un clone pulito il convertitore ha su cui girare.
4. **Il banner "fa fede l'italiano" si mostra anche qui**, come sui termini.
   Nota: a differenza dei termini, il documento privacy **non** contiene una
   clausola di prevalenza dell'italiano — il banner resta vero come fatto
   (i testi nascono in italiano) ma è una nostra affermazione, non una citazione
   del documento. Decisione del committente del 27/08/2026.

## Conversione markdown → HTML

Nuovo script `database/seeders/content/md-to-html.py`, fratello di
`docx-to-html.py`.

L'italiano si converte da solo. **L'inglese no**: il file italiano marca gli
elenchi con `*`, quello inglese li ha persi — le voci sono righe nude e
l'ultima si incolla al paragrafo successivo senza riga vuota. Un parser
markdown produrrebbe 89 voci di elenco in italiano e zero in inglese.

Le due redazioni hanno però **210 righe non vuote ciascuna, nello stesso
ordine**. Su questo si regge la conversione: la struttura si proietta per
posizione. `--structure-from docs/privacy-it.md` fa sì che ruolo di ogni riga e
`id` di ogni heading vengano dall'italiano, e solo il testo dall'inglese — la
stessa idea di `--ids-from` in `docx-to-html.py`, estesa dagli id all'intera
ossatura. Un'ancora a un capitolo vale così in entrambe le lingue.

Se una revisione futura sfasa i due file, il conteggio non torna e lo script
esce con errore invece di produrre HTML storto in silenzio.

### Regole di struttura

Dedotte dall'italiano, senza stringhe hardcoded:

| Sorgente | HTML |
| --- | --- |
| `1.` … `18.` | `<h2>` — i capitoli |
| `A.` … `I.` | `<h3>` — le finalità del capitolo 3 |
| blocco di una riga, senza punteggiatura finale, quando il blocco precedente non finisce con `:` | `<h3>` — le tre etichette del capitolo 2 |
| `* voce` | `<li>` dentro `<ul>` |
| blocco di due righe con la prima corta e senza punteggiatura finale | `<p><strong>prima</strong><br>seconda</p>` — i fornitori del capitolo 7 |
| ogni altro blocco | `<p>`, righe multiple unite da `<br>` |

Il vincolo sul `:` è quello che distingue un'etichetta da un valore introdotto
da due punti: senza, "Garante per la protezione dei dati personali" (capitolo
14) e l'indirizzo e-mail del capitolo 13 diventerebbero intestazioni.

Rese: 18 `<h2>`, 12 `<h3>`, 89 `<li>`, 3 `<strong>` di fornitore, per lingua.

Nessun auto-link: i testi non ne contengono e gli indirizzi e-mail restano
testo, come nel sorgente.

## Cablaggio

| Punto | Modifica |
| --- | --- |
| `Page` | `public const PRIVACY = 'privacy-policy'` |
| `PageSeeder` | entry con titolo "Privacy Policy" in entrambe le lingue |
| `lang/{it,en}/routes.php` | `'privacy' => 'privacy-policy'` (la parità la impone `LangParityTest`) |
| `routes/web.php` | una riga con `->defaults('slug', Page::PRIVACY)` |
| `site-footer.blade.php:45` | `href="#"` → `route('privacy')` |
| `partner-footer.blade.php:25` | `href="#"` → `route('privacy')` |
| `footer-minimal.blade.php:6` | `href="#"` → `route('privacy')` |

`last_updated_at` è `2026-08-27`, data di consegna dei testi: il documento non
ne porta una propria. Vale l'avvertenza già scritta per i termini — è un valore
a mano nel seeder, slegato dal contenuto: una revisione futura che aggiorna
l'HTML e dimentica la data pubblicherebbe un'informativa nuova con la data
vecchia.

Vista e CSS non cambiano: `LegalPage` e `.legal-content` reggono la pagina così
com'è.

## Test

In `LegalPagesTest`: la rotta italiana risponde 200 col proprio heading e la
data, l'inglese rende il corpo inglese col banner e senza italiano, la pagina
senza riga dà 404, **tutti e tre** i footer linkano la privacy.

In `LegalContentTest`: 18 capitoli numerati, ossatura inglese identica
all'italiana (livelli, id, conteggi di `<li>`/`<p>`/`<ul>`), numero del capitolo
nell'id coerente con quello scritto nell'heading, marcatori `*` consumati,
fornitori in grassetto. I due provider esistenti includono ora anche la privacy,
quindi la pagina passa anche dai controlli generici (HTML pulito, liste ben
formate) già scritti per i termini.

Il conteggio del seeder passa da 2 a 3 righe.

## Verificato a browser

`http://animalamo.test/privacy-policy` e `/en/privacy-policy`, ispezione del
DOM: 18/12/89 elementi per lingua, `h2` a 24px/700 in colore `ink`, elenchi con
`list-style: disc`, colonna di lettura a 900px, banner presente solo in
inglese, ancora `#13-diritti-dellinteressato` risolta anche sulla pagina
inglese, link Privacy attivo in tutti i footer, zero errori in console.

Lo screenshot non è stato possibile: bloccato dai permessi dell'ambiente.

## Difetto trovato in corsa

I primi test passavano ma il link Privacy del footer sotto la pagina stessa
puntava ancora a `#`: erano scritti dalla modifica (storefront, area partner)
invece che dalla superficie. I footer che espongono la voce sono **tre** —
`footer-minimal` è quello sotto contatti, carrello, checkout e le pagine legali,
cioè il più visto, ed era l'unico che nessun test copriva. Il test ora li elenca
tutti e tre.

## Da riferire al cliente

- Il capitolo 12 rimanda a una **Cookie Policy** che non esiste: non è stata
  fornita. I link "Cookie policy" e "Gestisci cookie" restano quindi `href="#"`
  in tutti i footer, e non c'è nessun sistema di gestione del consenso, che lo
  stesso capitolo 12 dà per presente ("l'apposito sistema di gestione del
  consenso presente sul sito").
- Il capitolo 7 elenca **DigitalOcean, Mailgun e Stripe** e aggiunge che
  l'elenco va verificato "sulla base della configurazione tecnica attiva del
  sito". Mailgun e Stripe sono in uso; l'hosting va confermato.
- Il capitolo 3 lettera G descrive una **newsletter** che oggi non esiste: la
  checkbox in registrazione scrive un booleano che nessuno legge.

## Fuori scope

Cookie policy, gestione del consenso cookie, CRUD di backoffice, checkbox di
accettazione in registrazione e registrazione del consenso, storicizzazione
delle versioni.
