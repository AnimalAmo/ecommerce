# Pagine legali: Termini e condizioni (clienti e fornitori)

Data: 2026-08-26

## Problema

Il footer promette due pagine che non esistono. `partials/site-footer.blade.php`
espone la voce `nav.footer.terms` ("Termini e condizioni") con `href="#"`, e
`partials/partner-footer.blade.php` fa lo stesso con `partner.footer_terms`. Le
label ci sono in entrambe le lingue, la destinazione no.

Il cliente ha fornito i due testi come documenti Word in `storage/`:

| File | Destinatario | Dimensione | Struttura |
| --- | --- | --- | --- |
| `condizioni_generali_clienti_senza_frase_stripe.docx` | Utenti finali | ~34.500 caratteri | 2 sezioni (A: condizioni comuni, B: strutture ricettive), 24 capitoli, 30 link |
| `Condizioni_Generali_Adesione_Fornitore_senza_punto_1_7.docx` | Fornitori/partner | ~28.000 caratteri | 17 capitoli, 17 link |

Nessuna immagine, nessuna tabella, entrambi solo in italiano. Sono ~62.000
caratteri di testo contrattuale strutturato: incollarli formattati dentro un
blade li renderebbe impossibili da revisionare e da tradurre.

## Decisioni prese

1. **Contenuto in DB tramite un model `Page`**, non in un blade e non in
   `lang/*.php`. Un backoffice per l'editing non esiste ancora ma è previsto:
   nascendo in DB, il CRUD si innesta dopo senza migrare i contenuti né
   riscrivere le viste.
2. **Il testo vive come file HTML versionati in git**, letti da un seeder. Git
   resta la fonte di verità per il testo (una revisione legale si legge come
   diff in PR), il DB è la sorgente a runtime.
3. **La versione inglese viene tradotta e seedata**. Va riletta da un legale
   prima della messa online: è testo contrattuale, non copy di marketing.

### Alternative scartate

| Approccio | Perché no |
| --- | --- |
| `Page` model con HTML inline nel seeder (heredoc) | 62k caratteri dentro un `.php`: il diff di una modifica contrattuale diventa illeggibile in review |
| Solo blade + `lang/*.php` | Coerente col pattern attuale delle pagine di contenuto, ma col CMS previsto impone di migrare tutto dopo |
| Iubenda / servizio esterno | Adatto a privacy e cookie policy, non a condizioni generali redatte su misura dal legale del cliente |

## Dati

Migrazione `create_pages_table`:

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->json('title');
    $table->json('body');
    $table->date('last_updated_at')->nullable();
    $table->timestamps();
});
```

`app/Models/Page/Page.php` — cartella per modello, come `Faq/`, `Event/`,
`Structure/`. Usa `Spatie\Translatable\HasTranslations` con
`public array $translatable = ['title', 'body']`, la convenzione già in
`Structure.php:23`.

Due costanti di classe come chiavi stabili:

```php
public const TERMS_CUSTOMERS = 'termini-e-condizioni';
public const TERMS_SUPPLIERS = 'termini-e-condizioni-fornitori';
```

Lo `slug` è la chiave d'identità del contenuto, **non** l'URL: gli URL sono
localizzati e vivono in `lang/{it,en}/routes.php`. Tenerli separati evita di
dover cambiare la chiave del contenuto se domani cambia lo slug pubblico.

`last_updated_at` è una data esplicita, non `updated_at`: rieseguire il seeder
non deve far sembrare aggiornato un testo che non è cambiato. Se è `null` la
riga "Ultimo aggiornamento" non compare.

## Contenuti e seeder

Quattro file in `database/seeders/content/`:

```
termini-e-condizioni.it.html
termini-e-condizioni.en.html
termini-e-condizioni-fornitori.it.html
termini-e-condizioni-fornitori.en.html
```

`PageSeeder` fa `updateOrCreate` sullo slug leggendo i quattro file, ed entra in
`DatabaseSeeder` nel blocco **non** demo: è contenuto istituzionale, non dati di
prova, e deve esistere anche in produzione.

> Quando arriverà il CRUD di backoffice, `updateOrCreate` va cambiato in
> create-only (`firstOrCreate`), altrimenti un `db:seed` sovrascrive le
> modifiche fatte dal cliente.

### Conversione docx → HTML

Uno script one-shot committato in `database/seeders/content/docx-to-html.py`,
così la prossima revisione del cliente costa un comando invece di un lavoro
manuale. Mappatura:

| Sorgente Word | HTML |
| --- | --- |
| stile `Titolo1` (clienti: "Sezione A", "Sezione B") | `<h2>` |
| stile `Titolo2` (clienti: i 24 capitoli) | `<h3>` |
| `Paragrafoelenco` in grassetto di primo livello (fornitori: i 17 capitoli) | `<h3>` con la numerazione automatica Word risolta in testo |
| `numPr` (liste numerate/puntate, anche annidate) | `<ol>` / `<ul>` |
| run in grassetto / corsivo | `<strong>` / `<em>` |
| `w:hyperlink` risolto dai `word/_rels` | `<a href>`, con `rel="noopener"` sugli esterni |
| Sommario Word (`Titolosommario`, `Sommario1`, `Sommario2`) | scartato: i numeri di pagina non significano niente a schermo |

Ogni heading riceve un `id` derivato dal titolo, così i capitoli sono
linkabili. L'HTML prodotto non contiene `style=`, `class=` né `<span>` di Word:
solo tag semantici.

## Rotte

Nuove chiavi in `lang/it/routes.php`:

```php
'terms.customers' => 'termini-e-condizioni',
'terms.suppliers' => 'termini-e-condizioni-fornitori',
```

e in `lang/en/routes.php`:

```php
'terms.customers' => 'terms-and-conditions',
'terms.suppliers' => 'supplier-terms-and-conditions',
```

`LangParityTest` impone la parità delle chiavi fra `it` e `en`: vanno aggiunte
in entrambi i file o la suite fallisce.

Dentro il gruppo localizzato di `routes/web.php`, accanto alle altre pagine di
contenuto:

```php
Route::get(LaravelLocalization::transRoute('routes.terms.customers'), LegalPage::class)
    ->defaults('slug', Page::TERMS_CUSTOMERS)->name('terms.customers');
Route::get(LaravelLocalization::transRoute('routes.terms.suppliers'), LegalPage::class)
    ->defaults('slug', Page::TERMS_SUPPLIERS)->name('terms.suppliers');
```

## Componente e vista

`App\Livewire\Content\LegalPage` — un solo componente per entrambe le pagine:

```php
public Page $page;

public function mount(string $slug): void
{
    $this->page = Page::where('slug', $slug)->firstOrFail();
}

public function render()
{
    return view('livewire.content.legal-page')->title($this->page->title);
}
```

Il passaggio dello slug si appoggia ai `defaults()` di rotta. Va verificato con
un test che Livewire 4 li risolva come parametri di `mount()`; se non lo fa, il
ripiego è due componenti sottili (`CustomerTerms`, `SupplierTerms`) che
estendono una base astratta con la logica comune. Nessuna delle due varianti
cambia rotte, dati o vista.

`resources/views/livewire/content/legal-page.blade.php`:

- `partials.site-header` + `partials.footer-minimal`, il pattern delle pagine
  secondarie già usato da contatti, checkout, carrello e "lavora con noi";
- `<h1>` con `$page->title`, sotto la riga "Ultimo aggiornamento" (nascosta se
  `last_updated_at` è `null`);
- il corpo dentro `<div class="legal-content">{!! $page->body !!}</div>`;
- container centrato con il solito `$px`, colonna di lettura a larghezza
  limitata (il testo lungo a 1600px è illeggibile).

### Tipografia

Tailwind 4 senza plugin typography. Invece di aggiungere una dipendenza npm,
un blocco `.legal-content` in `resources/css/app.css` che stila `h2`, `h3`, `p`,
`ol`, `ul`, `li`, `a`, `strong` con i token del progetto (`ink`, `brand-cyan`
per i link, Nunito già di default). Meno di 30 righe di CSS, zero build nuove.

### HTML non escapato

`{!! !!}` è deliberato: il corpo è HTML strutturato, non testo. Oggi la sorgente
è il nostro seeder, quindi non c'è superficie XSS. Quando arriverà l'editor di
backoffice servirà sanitizzare l'input lato CMS (allowlist di tag), perché a
quel punto il contenuto diventa semi-fidato. Fuori dallo scope di questo lavoro,
ma è la ragione per cui il CRUD non va aggiunto senza pensarci.

## Versione inglese

> Aggiornato il 2026-08-26: la decisione iniziale era che la traduzione la
> producessi io, con l'avvertenza di farla rileggere da un legale. Il cliente ha
> poi fornito i due documenti già tradotti — `storage/Animal amo inglese 1.docx`
> (fornitori) e `Animal amo inglese 2.docx` (clienti) — quindi l'inglese nasce da
> una seconda conversione, non da una traduzione nostra, e il rischio legale
> sparisce.

I due documenti inglesi usano stili Word diversi dagli italiani (`Heading1` e
`ListBullet` invece di `Titolo1`/`Titolo2`/`Paragrafoelenco`) e mettono l'indice
in testa senza uno stile dedicato: il convertitore riconosce il dialetto e
scarta l'indice individuando la seconda occorrenza del primo heading.

Gli `id` degli heading inglesi **non** derivano dal testo inglese: sono presi per
posizione dal file italiano, così un'ancora a un capitolo funziona in entrambe le
lingue.

Entrambi i documenti contengono la clausola secondo cui la versione italiana è
l'originale e prevale (punto 2.4): il banner mostrato fuori dall'italiano dice la
stessa cosa in forma visibile.

Tre divergenze fra le due redazioni, tutte contenuto del cliente, da riferire a
lui e non da correggere in codice:

- l'italiano dei clienti linka la privacy policy di **booking.com** (URL con
  `gclid` e `aid` affiliato) al punto 8.1; l'inglese dice "the relevant Privacy
  and Cookie Policy" senza link, correggendo di fatto l'errore;
- il link alla piattaforma ODR della Commissione europea è cliccabile in
  italiano e testo semplice in inglese;
- nell'italiano il capitolo 24 si intitola "Recensioni e rating" ma la clausola
  finale ex art. 1341 c.c. lo richiama come "Feedback e rating".

## Link nel footer

| File | Riga | Da | A |
| --- | --- | --- | --- |
| `partials/site-footer.blade.php` | 44 | `href="#"` | `route('terms.customers')` |
| `partials/partner-footer.blade.php` | 27 | `href="#"` | `route('terms.suppliers')` |

Le label esistono già in `nav.footer.terms` e `partner.footer_terms`, in
entrambe le lingue. Privacy, cookie policy e "gestisci cookie" restano `href="#"`:
sono documenti diversi, non forniti.

## Test

`tests/Feature/Content/LegalPagesTest.php`:

- le due rotte italiane rispondono 200 e contengono un heading noto del proprio
  documento;
- la rotta inglese rende il corpo inglese e non quello italiano;
- il seeder è idempotente: due esecuzioni lasciano 2 righe, non 4;
- uno slug inesistente dà 404.

Trappola nota: il testo legale è pieno di apostrofi tipografici (`’`).
`assertSee()` escapa di default e fallisce su stringhe che nella pagina ci sono
davvero — usare `assertSee($testo, false)` o scegliere frasi senza apostrofi.

I test girano solo dentro la VM (host PHP 8.2, app ≥8.3), come da CLAUDE.md.

## Fuori scope

- CRUD di backoffice per editare le pagine (previsto, non ora).
- Checkbox di accettazione delle condizioni in registrazione utente e in
  adesione partner, e registrazione del consenso.
- Privacy policy, cookie policy, gestione cookie.
- Storicizzazione delle versioni e tracciamento di quale versione ha accettato
  ciascun utente.
- Indice ancorato/sticky dei capitoli in pagina: gli `id` sugli heading vengono
  generati, il widget di navigazione no.

## Debito noto

Cose viste, valutate e lasciate com'erano. Nessuna è un difetto attivo: sono
scritte qui perché la prossima persona che tocca queste pagine non debba
riscoprirle.

- `Page::translationOr()` dichiara `: string` ma restituirebbe `null` se
  mancasse anche l'italiano, e il suo `?:` tratta una traduzione vuota come
  mancante. Impossibile oggi: il seeder scrive sempre l'italiano. Da chiudere
  col CRUD di backoffice, quando un editor potrà svuotare un campo.
- `slugify()` tronca gli `id` a 60 caratteri a metà parola. Verificato: zero
  collisioni sui 43 heading dei quattro documenti.
- `test_the_seeder_is_idempotent` asserisce solo il numero di righe, quindi
  passerebbe identico con `firstOrCreate`. Va rafforzato quando il seeder
  diventerà create-only, non prima.
- `host.endswith(UNLINKED_HOSTS)` in `docx-to-html.py` è un suffisso di stringa,
  non un confine di dominio: può solo bloccare in eccesso.
- Lo stack delle liste del convertitore traccia `(tag, ilvl)` ma non `numFmt`:
  due `<ol>` adiacenti con formati diversi allo stesso livello condividerebbero
  un `type`. Oggi impossibile — c'è un solo `<ol>` in tutti e quattro i file.
- `--ids-from` senza valore solleva `IndexError` invece di un errore pulito.
- `reloadRoutesFor()` in `LegalPagesTest` è copiato da `LocalizationTest`: due
  copie di codice d'infrastruttura delicato che col tempo divergeranno.
- I link dentro `.legal-content` usano `brand-cyan` su bianco, contrasto ~1.75:1,
  sotto la soglia WCAG AA. La sottolineatura li rende comunque riconoscibili e
  nel corpus sopravvive un solo link.
- `last_updated_at` è scritto a mano nel `PageSeeder`, senza niente che lo leghi
  al contenuto: una revisione futura che aggiorna l'HTML e dimentica la data
  pubblicherebbe condizioni nuove datate 26/08/2026.

## Nota operativa

I sei `.docx` in `storage/` non sono gitignorati e risultano untracked: un
`git add .` li spedirebbe sul remoto. **Decisione del committente (26/08/2026):
restano così**, né committati né ignorati — il rischio è stato posto e accettato.

Conseguenza da tenere presente: da un clone pulito il convertitore
`database/seeders/content/docx-to-html.py` non ha niente su cui girare. I quattro
documenti autoritativi (due italiani, due inglesi) vivono solo sulla macchina di
sviluppo; le due copie con il suffisso `(1)` sono byte-identiche agli originali,
rimandate dal cliente per errore.
