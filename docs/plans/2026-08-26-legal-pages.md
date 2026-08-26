# Pagine legali (Termini e condizioni) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pubblicare le due pagine "Termini e condizioni" — clienti e fornitori — in italiano e inglese, con il testo che vive in DB tramite un model `Page` e nasce da file HTML versionati in git.

**Architecture:** I due `.docx` in `storage/` vengono convertiti una tantum in HTML semantico da uno script Python committato; i file HTML stanno in `database/seeders/content/` e un `PageSeeder` li carica nella tabella `pages` (colonne `title`/`body` translatable via spatie). Un solo componente Livewire `Content\LegalPage` serve entrambe le pagine, distinguendole per slug passato dai `defaults()` di rotta.

**Tech Stack:** Laravel 13, Livewire 4, Flux 2 Pro, Tailwind 4 (CSS-first), `spatie/laravel-translatable` 6, `mcamara/laravel-localization` 2, PHPUnit 12.

Spec di riferimento: [docs/specs/2026-08-26-legal-pages-design.md](../specs/2026-08-26-legal-pages-design.md).

## Global Constraints

- **Flux al posto degli elementi nativi**: mai un `<button>` nativo, sempre `flux:button`. Un `<a>` semplice va bene per link di testo e navigazione — e il corpo legale è fatto di quelli.
- **Design token, non hex grezzi**, dove il token esiste (`ink`, `brand-cyan`, `gray-150`, …, definiti in `@theme` dentro `resources/css/app.css`).
- **Link interni con `route()`**: mcamara antepone il prefisso di lingua da solo, un `href="/..."` scritto a mano lo perde in silenzio.
- **Commit**: Conventional Commits, in inglese, **nessun trailer di attribuzione AI**. Non pushare se non richiesto.
- **I test girano SOLO dentro la VM** — il PHP dell'host è 8.2, l'app vuole ≥8.3:
  `ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=X'`
- **`vendor/bin/pint`** prima di ogni commit (gira bene sull'host).
- **`npm run build`** prima di qualunque deploy: le classi Tailwind nuove non esistono nelle build vecchie.
- I due `.docx` in `storage/` **non sono gitignorati e sono untracked**: mai `git add .`, sempre `git add <percorsi espliciti>`.

## File Structure

| File | Responsabilità |
| --- | --- |
| `database/migrations/2026_08_26_120001_create_pages_table.php` | schema `pages` |
| `app/Models/Page/Page.php` | model translatable, slug costanti, fallback di lingua |
| `database/seeders/content/docx-to-html.py` | conversione one-shot docx → HTML semantico |
| `database/seeders/content/*.{it,en}.html` | i quattro corpi delle pagine |
| `database/seeders/PageSeeder.php` | carica i file nella tabella `pages` |
| `app/Livewire/Content/LegalPage.php` | risolve lo slug, passa la pagina alla vista |
| `resources/views/livewire/content/legal-page.blade.php` | intestazione, data, corpo |
| `resources/css/app.css` | blocco `.legal-content` (tipografia del testo lungo) |
| `lang/{it,en}/legal.php` | etichette di contorno ("Ultimo aggiornamento", nota sulla lingua) |
| `lang/{it,en}/routes.php` | i due slug per lingua |
| `routes/web.php` | le due rotte localizzate |
| `resources/views/partials/{site,partner}-footer.blade.php` | i due link che oggi sono `#` |
| `tests/Feature/Content/LegalPagesTest.php` | rotte, lingue, 404 |
| `tests/Feature/Content/LegalContentTest.php` | invarianti dei file HTML generati e del seeder |

---

### Task 1: Tabella `pages` e model `Page`

**Files:**
- Create: `database/migrations/2026_08_26_120001_create_pages_table.php`
- Create: `app/Models/Page/Page.php`
- Test: `tests/Unit/PageTest.php`

**Interfaces:**
- Consumes: niente (primo task).
- Produces: `App\Models\Page\Page` con costanti `Page::TERMS_CUSTOMERS` (`'termini-e-condizioni'`) e `Page::TERMS_SUPPLIERS` (`'termini-e-condizioni-fornitori'`); attributi `slug` (string), `title`/`body` (translatable), `last_updated_at` (`?Carbon`); metodi `titleFor(?string $locale = null): string` e `bodyFor(?string $locale = null): string`.

- [ ] **Step 1: Scrivi il test che fallisce**

`tests/Unit/PageTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Page\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_and_body_are_translatable(): void
    {
        $page = Page::create([
            'slug' => Page::TERMS_CUSTOMERS,
            'title' => ['it' => 'Termini e condizioni', 'en' => 'Terms and conditions'],
            'body' => ['it' => '<p>Testo italiano</p>', 'en' => '<p>English text</p>'],
        ]);

        $this->assertSame('Termini e condizioni', $page->titleFor('it'));
        $this->assertSame('<p>English text</p>', $page->bodyFor('en'));
    }

    public function test_a_missing_translation_falls_back_to_italian(): void
    {
        $page = Page::create([
            'slug' => Page::TERMS_SUPPLIERS,
            'title' => ['it' => 'Condizioni fornitore'],
            'body' => ['it' => '<p>Solo italiano</p>'],
        ]);

        // Il testo di partenza è italiano: una pagina senza traduzione non
        // deve uscire vuota, altrimenti /en/... mostra un corpo bianco.
        $this->assertSame('Condizioni fornitore', $page->titleFor('en'));
        $this->assertSame('<p>Solo italiano</p>', $page->bodyFor('en'));
    }

    public function test_last_updated_at_is_a_date_and_may_be_null(): void
    {
        $page = Page::create([
            'slug' => 'test',
            'title' => ['it' => 'T'],
            'body' => ['it' => '<p>B</p>'],
            'last_updated_at' => '2026-08-26',
        ]);

        $this->assertSame('26/08/2026', $page->last_updated_at->format('d/m/Y'));
        $this->assertNull(Page::create([
            'slug' => 'test-2', 'title' => ['it' => 'T'], 'body' => ['it' => '<p>B</p>'],
        ])->last_updated_at);
    }
}
```

- [ ] **Step 2: Esegui il test e verifica che fallisca**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=PageTest'
```

Atteso: FAIL con `Class "App\Models\Page\Page" not found`.

- [ ] **Step 3: Scrivi la migrazione**

`database/migrations/2026_08_26_120001_create_pages_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('body');
            // Data dichiarata della revisione, non updated_at: rieseguire il
            // seeder non deve far sembrare aggiornato un testo immutato.
            $table->date('last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
```

- [ ] **Step 4: Scrivi il model**

`app/Models/Page/Page.php`:

```php
<?php

namespace App\Models\Page;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Pagina di contenuto redazionale servita da DB (oggi: le due pagine legali).
 * Il testo nasce dai file in database/seeders/content/ ed è caricato dal
 * PageSeeder: git resta la fonte di verità, il DB la sorgente a runtime.
 */
class Page extends Model
{
    use HasTranslations;

    public const TERMS_CUSTOMERS = 'termini-e-condizioni';

    public const TERMS_SUPPLIERS = 'termini-e-condizioni-fornitori';

    /** Lingua in cui i documenti sono redatti: è lei a fare da rete. */
    public const SOURCE_LOCALE = 'it';

    /** @var array<int, string> */
    public array $translatable = ['title', 'body'];

    /** @var list<string> */
    protected $fillable = ['slug', 'title', 'body', 'last_updated_at'];

    public function titleFor(?string $locale = null): string
    {
        return $this->translationOr('title', $locale);
    }

    public function bodyFor(?string $locale = null): string
    {
        return $this->translationOr('body', $locale);
    }

    /**
     * spatie ricade su app.fallback_locale ('en'), che qui è la lingua
     * tradotta, non l'originale: se manca l'inglese la pagina uscirebbe
     * vuota. Il ripiego esplicito è l'italiano, la lingua dei documenti.
     */
    private function translationOr(string $key, ?string $locale): string
    {
        $locale ??= app()->getLocale();

        return $this->getTranslation($key, $locale, false)
            ?: $this->getTranslation($key, self::SOURCE_LOCALE, false);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['last_updated_at' => 'date'];
    }
}
```

- [ ] **Step 5: Esegui il test e verifica che passi**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=PageTest'
```

Atteso: PASS, 3 test.

- [ ] **Step 6: Pint e commit**

```bash
vendor/bin/pint app/Models/Page/Page.php database/migrations/2026_08_26_120001_create_pages_table.php tests/Unit/PageTest.php
git add app/Models/Page/Page.php database/migrations/2026_08_26_120001_create_pages_table.php tests/Unit/PageTest.php
git commit -m "feat(content): add the Page model for DB-backed editorial pages"
```

---

### Task 2: Conversione dei due .docx in HTML semantico (italiano)

**Files:**
- Create: `database/seeders/content/docx-to-html.py`
- Create: `database/seeders/content/termini-e-condizioni.it.html`
- Create: `database/seeders/content/termini-e-condizioni-fornitori.it.html`
- Test: `tests/Feature/Content/LegalContentTest.php`

**Interfaces:**
- Consumes: le costanti slug di `Page` (Task 1) per i nomi dei file.
- Produces: i due file `*.it.html`, con questa forma: `<h2 id="...">` per le due sezioni A/B del documento clienti, `<h3 id="...">` per ogni capitolo dei due documenti, `<p>`, `<ol>`, `<ul>`, `<li>`, `<strong>`, `<em>`, `<a href target rel>`. Nessun attributo `style`/`class`, nessuno `<span>`, nessun `<h1>` (è del blade).

**Fatti accertati sui documenti** (non riderivarli):
- La numerazione delle clausole (`1.1`, `22.2.21`) è **testo letterale** dentro i paragrafi in entrambi i documenti: non va ricostruita.
- L'unica numerazione automatica che conta è quella dei **17 capitoli del documento fornitori** (paragrafi stile `Paragrafoelenco`, primo livello, tutto in grassetto): vanno numerati 1..17 nell'ordine, e i numeri devono coincidere col Sommario del documento (`1.Premesse`, `2.Definizioni`, …, `17.Foro competente e legislazione applicabile`).
- Nel documento clienti i capitoli sono stile `Titolo2` e **hanno già il numero nel testo** ("1. Definizioni"); le due sezioni sono stile `Titolo1`.
- Il Sommario di Word (stili `Titolosommario`, `Sommario1`, `Sommario2`) va scartato.
- Nessuna immagine, nessuna tabella in nessuno dei due.

- [ ] **Step 1: Scrivi il test che fallisce**

`tests/Feature/Content/LegalContentTest.php`:

```php
<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Invarianti dei file HTML generati da docx-to-html.py. Servono a far fallire
 * la suite se una riconversione futura reintroduce lo sporco di Word.
 */
class LegalContentTest extends TestCase
{
    /** @return array<int, array{0: string, 1: int}> slug e numero atteso di capitoli */
    public static function fileProvider(): array
    {
        return [
            [Page::TERMS_CUSTOMERS, 24],
            [Page::TERMS_SUPPLIERS, 17],
        ];
    }

    #[DataProvider('fileProvider')]
    public function test_the_italian_html_is_clean_and_complete(string $slug, int $chapters): void
    {
        $path = database_path("seeders/content/{$slug}.it.html");
        $this->assertFileExists($path);

        $html = file_get_contents($path);

        $this->assertSame($chapters, substr_count($html, '<h3 id='), 'Capitoli mancanti o duplicati');
        $this->assertStringNotContainsString('<span', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('class=', $html);
        $this->assertStringNotContainsString('<h1', $html, "L'h1 è del blade, non del corpo");
        $this->assertStringNotContainsString('Sommario', $html, 'Il sommario di Word va scartato');
        $this->assertMatchesRegularExpression('/<h3 id="[a-z0-9-]+">/', $html);
    }

    public function test_the_customer_document_keeps_its_two_sections(): void
    {
        $html = file_get_contents(database_path('seeders/content/'.Page::TERMS_CUSTOMERS.'.it.html'));

        $this->assertSame(2, substr_count($html, '<h2 id='));
        $this->assertStringContainsString('Sezione A', $html);
        $this->assertStringContainsString('Sezione B', $html);
    }

    public function test_the_supplier_chapters_are_numbered_like_the_source(): void
    {
        $html = file_get_contents(database_path('seeders/content/'.Page::TERMS_SUPPLIERS.'.it.html'));

        $this->assertStringContainsString('1. Premesse', $html);
        $this->assertStringContainsString('2. Definizioni', $html);
        $this->assertStringContainsString('17. Foro competente', $html);
    }

    public function test_external_links_open_safely(): void
    {
        foreach ([Page::TERMS_CUSTOMERS, Page::TERMS_SUPPLIERS] as $slug) {
            $html = file_get_contents(database_path("seeders/content/{$slug}.it.html"));

            preg_match_all('/<a href="http[^"]*"[^>]*>/', $html, $matches);

            foreach ($matches[0] as $anchor) {
                $this->assertStringContainsString('rel="noopener"', $anchor);
            }
        }
    }
}
```

- [ ] **Step 2: Esegui il test e verifica che fallisca**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=LegalContentTest'
```

Atteso: FAIL con `Failed asserting that file ".../termini-e-condizioni.it.html" exists`.

- [ ] **Step 3: Scrivi il convertitore**

`database/seeders/content/docx-to-html.py`:

```python
#!/usr/bin/env python3
"""
Converte i .docx delle condizioni generali in HTML semantico per il PageSeeder.

    python3 database/seeders/content/docx-to-html.py <sorgente.docx> <destinazione.html>

Cosa fa:
  * Titolo1 -> <h2>, Titolo2 -> <h3>, con id ricavato dal testo;
  * nel documento fornitori i Paragrafoelenco di primo livello tutti in
    grassetto sono i capitoli: diventano <h3> numerati 1..N;
  * le liste numPr diventano <ol>/<ul> secondo il numFmt di numbering.xml;
  * gli hyperlink esterni diventano <a target="_blank" rel="noopener">;
  * il Sommario di Word viene scartato.

Cosa NON fa: toccare la numerazione delle clausole (1.1, 22.2.21), che nei
documenti è già testo letterale.

Nota: usa xml.etree della stdlib. È uno script one-shot che gira in locale su
due file forniti dal cliente, non un parser esposto a input non fidato; se un
giorno dovesse leggere docx di provenienza ignota, passare a defusedxml.
"""

import html
import re
import sys
import unicodedata
import zipfile
from xml.etree import ElementTree as ET

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
R = '{http://schemas.openxmlformats.org/officeDocument/2006/relationships}'
TOC_STYLES = {'Titolosommario', 'Sommario1', 'Sommario2', 'Sommario3'}


def load(path):
    archive = zipfile.ZipFile(path)
    body = ET.fromstring(archive.read('word/document.xml')).find(W + 'body')
    rels = {
        rel.get('Id'): rel.get('Target')
        for rel in ET.fromstring(archive.read('word/_rels/document.xml.rels'))
    }
    return body, rels, numbering_formats(archive)


def numbering_formats(archive):
    """numId -> numFmt del livello 0 ('bullet', 'decimal', ...)."""
    try:
        root = ET.fromstring(archive.read('word/numbering.xml'))
    except KeyError:
        return {}

    abstract = {}
    for node in root.findall(W + 'abstractNum'):
        level = node.find(f'{W}lvl[@{W}ilvl="0"]')
        fmt = level.find(W + 'numFmt') if level is not None else None
        abstract[node.get(W + 'abstractNumId')] = fmt.get(W + 'val') if fmt is not None else 'decimal'

    formats = {}
    for node in root.findall(W + 'num'):
        ref = node.find(W + 'abstractNumId')
        formats[node.get(W + 'numId')] = abstract.get(ref.get(W + 'val'), 'decimal')
    return formats


def style_of(paragraph):
    properties = paragraph.find(W + 'pPr')
    if properties is None:
        return None
    style = properties.find(W + 'pStyle')
    return style.get(W + 'val') if style is not None else None


def numbering_of(paragraph):
    """(numId, ilvl) se il paragrafo sta in una lista, altrimenti None."""
    properties = paragraph.find(W + 'pPr')
    if properties is None:
        return None
    numbering = properties.find(W + 'numPr')
    if numbering is None:
        return None
    num_id = numbering.find(W + 'numId')
    level = numbering.find(W + 'ilvl')
    return (
        num_id.get(W + 'val') if num_id is not None else '0',
        int(level.get(W + 'val')) if level is not None else 0,
    )


def run_html(run):
    text = ''.join(node.text or '' for node in run.findall(W + 't'))
    if not text:
        return '<br>' if run.find(W + 'br') is not None else ''

    out = html.escape(text)
    properties = run.find(W + 'rPr')
    if properties is not None:
        if properties.find(W + 'i') is not None:
            out = f'<em>{out}</em>'
        if properties.find(W + 'b') is not None:
            out = f'<strong>{out}</strong>'
    return out


def inline_html(paragraph, rels):
    parts = []
    for child in paragraph:
        if child.tag == W + 'r':
            parts.append(run_html(child))
        elif child.tag == W + 'hyperlink':
            inner = ''.join(run_html(run) for run in child.findall(W + 'r'))
            target = rels.get(child.get(R + 'id'), '')
            if target.startswith('http'):
                href = html.escape(target, quote=True)
                parts.append(f'<a href="{href}" target="_blank" rel="noopener">{inner}</a>')
            else:
                parts.append(inner)

    joined = ''.join(parts)
    # Word spezza una frase in grassetto su più run: </strong><strong> sparisce.
    joined = re.sub(r'</(strong|em)>(\s*)<\1>', r'\2', joined)
    return re.sub(r'\s+', ' ', joined).strip()


def is_all_bold(paragraph):
    runs = [
        run for run in paragraph.findall(W + 'r')
        if ''.join(node.text or '' for node in run.findall(W + 't')).strip()
    ]
    if not runs:
        return False
    return all(
        run.find(W + 'rPr') is not None and run.find(W + 'rPr').find(W + 'b') is not None
        for run in runs
    )


def plain(markup):
    return re.sub(r'<[^>]+>', '', markup).strip()


def slugify(text):
    ascii_text = unicodedata.normalize('NFKD', plain(text)).encode('ascii', 'ignore').decode()
    slug = re.sub(r'[^a-z0-9]+', '-', ascii_text.lower()).strip('-')
    return slug[:60].strip('-') or 'sezione'


def convert(source):
    body, rels, formats = load(source)
    out, stack, chapter = [], [], 0

    def close_lists(depth=0):
        while len(stack) > depth:
            out.append(f'</{stack.pop()}>')

    for paragraph in body.iter(W + 'p'):
        style = style_of(paragraph)
        if style in TOC_STYLES:
            continue

        inner = inline_html(paragraph, rels)
        if not inner:
            continue

        if style == 'Titolo1':
            close_lists()
            out.append(f'<h2 id="{slugify(inner)}">{plain(inner)}</h2>')
            continue

        if style == 'Titolo2':
            close_lists()
            out.append(f'<h3 id="{slugify(inner)}">{plain(inner)}</h3>')
            continue

        numbering = numbering_of(paragraph)

        if style == 'Paragrafoelenco' and numbering and numbering[1] == 0 and is_all_bold(paragraph):
            close_lists()
            chapter += 1
            label = f'{chapter}. {plain(inner)}'
            out.append(f'<h3 id="{slugify(label)}">{label}</h3>')
            continue

        if numbering:
            tag = 'ul' if formats.get(numbering[0]) == 'bullet' else 'ol'
            depth = numbering[1] + 1
            close_lists(depth)
            while len(stack) < depth:
                stack.append(tag)
                out.append(f'<{tag}>')
            out.append(f'<li>{inner}</li>')
            continue

        close_lists()
        out.append(f'<p>{inner}</p>')

    close_lists()
    return '\n'.join(out) + '\n'


if __name__ == '__main__':
    if len(sys.argv) != 3:
        raise SystemExit(__doc__)

    with open(sys.argv[2], 'w', encoding='utf-8') as destination:
        destination.write(convert(sys.argv[1]))
```

- [ ] **Step 4: Genera i due file italiani**

```bash
python3 database/seeders/content/docx-to-html.py \
  "storage/condizioni_generali_clienti_senza_frase_stripe.docx" \
  database/seeders/content/termini-e-condizioni.it.html
python3 database/seeders/content/docx-to-html.py \
  "storage/Condizioni_Generali_Adesione_Fornitore_senza_punto_1_7.docx" \
  database/seeders/content/termini-e-condizioni-fornitori.it.html
```

- [ ] **Step 5: Ispeziona l'output e itera finché il test passa**

Il convertitore va rieseguito dopo ogni correzione. Cose da guardare a occhio prima di fidarsi del test:

- il primo capitolo del documento fornitori è `<h3 id="1-premesse">1. Premesse</h3>` e l'ultimo è il 17°;
- il documento clienti apre con `<h2>Sezione A: …</h2>` e ha `<h2>Sezione B: …</h2>` a metà;
- le clausole `1.1`, `1.2`, … compaiono come testo dentro i `<p>`, non come numeri di lista;
- le liste puntate (le definizioni: "Utente:", "Fornitore:", …) sono `<ul>`, non `<ol>`;
- nessun `<li>` rimasto fuori da un `<ol>`/`<ul>`, nessun tag non chiuso.

Controllo strutturale rapido:

```bash
python3 -c "
import re,sys
for f in ['database/seeders/content/termini-e-condizioni.it.html','database/seeders/content/termini-e-condizioni-fornitori.it.html']:
    h=open(f,encoding='utf8').read()
    tags=re.findall(r'</?(\w+)',h)
    print(f, {t:tags.count(t) for t in sorted(set(tags))})
"
```

- [ ] **Step 6: Esegui il test e verifica che passi**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=LegalContentTest'
```

Atteso: PASS, 5 test (2 dal data provider + 3 singoli).

- [ ] **Step 7: Commit**

```bash
git add database/seeders/content/docx-to-html.py \
        database/seeders/content/termini-e-condizioni.it.html \
        database/seeders/content/termini-e-condizioni-fornitori.it.html \
        tests/Feature/Content/LegalContentTest.php
git commit -m "feat(content): convert the client-provided terms documents to semantic HTML"
```

---

### Task 3: `PageSeeder`

**Files:**
- Create: `database/seeders/PageSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (lista `$this->call([...])` del blocco non-demo)
- Test: `tests/Feature/Content/LegalContentTest.php` (aggiunta di casi)

**Interfaces:**
- Consumes: `Page` (Task 1), i file `database/seeders/content/{slug}.{locale}.html` (Task 2).
- Produces: `Database\Seeders\PageSeeder`, che dopo l'esecuzione lascia esattamente una riga `pages` per slug, con `title` e `body` popolati per ogni file presente.

- [ ] **Step 1: Scrivi il test che fallisce**

Aggiungi in coda a `tests/Feature/Content/LegalContentTest.php` (e aggiungi `use Database\Seeders\PageSeeder;`, `use Illuminate\Foundation\Testing\RefreshDatabase;` più `use RefreshDatabase;` nella classe):

```php
    public function test_the_seeder_loads_both_pages(): void
    {
        $this->seed(PageSeeder::class);

        $this->assertSame(2, Page::count());

        $customers = Page::where('slug', Page::TERMS_CUSTOMERS)->sole();
        $this->assertSame('Termini e condizioni', $customers->titleFor('it'));
        $this->assertStringContainsString('<h3 id=', $customers->bodyFor('it'));
        $this->assertNotNull($customers->last_updated_at);
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(PageSeeder::class);
        $this->seed(PageSeeder::class);

        $this->assertSame(2, Page::count());
    }
```

- [ ] **Step 2: Esegui il test e verifica che fallisca**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=LegalContentTest'
```

Atteso: FAIL con `Class "Database\Seeders\PageSeeder" not found`.

- [ ] **Step 3: Scrivi il seeder**

`database/seeders/PageSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Page\Page;
use Illuminate\Database\Seeder;

/**
 * Carica le pagine redazionali dai file HTML versionati in seeders/content/.
 * Il testo sta in git — una revisione legale si legge come diff — e il DB è
 * solo la sorgente a runtime.
 *
 * ATTENZIONE: quando arriverà il CRUD di backoffice questo updateOrCreate va
 * cambiato in firstOrCreate, altrimenti un db:seed cancella le modifiche fatte
 * dal cliente.
 */
class PageSeeder extends Seeder
{
    /** @var array<string, array{title: array<string, string>, last_updated_at: string}> */
    private const PAGES = [
        Page::TERMS_CUSTOMERS => [
            'title' => ['it' => 'Termini e condizioni', 'en' => 'Terms and conditions'],
            'last_updated_at' => '2026-08-26',
        ],
        Page::TERMS_SUPPLIERS => [
            'title' => ['it' => 'Condizioni generali di adesione fornitore', 'en' => 'Supplier general terms of adhesion'],
            'last_updated_at' => '2026-08-26',
        ],
    ];

    private const LOCALES = ['it', 'en'];

    public function run(): void
    {
        foreach (self::PAGES as $slug => $attributes) {
            $body = $this->bodies($slug);

            if ($body === []) {
                continue;
            }

            Page::updateOrCreate(['slug' => $slug], [
                'title' => $attributes['title'],
                'body' => $body,
                'last_updated_at' => $attributes['last_updated_at'],
            ]);
        }
    }

    /**
     * Le lingue senza file vengono saltate: il model ripiega sull'italiano,
     * così una traduzione che arriva dopo non blocca la pubblicazione.
     *
     * @return array<string, string>
     */
    private function bodies(string $slug): array
    {
        $bodies = [];

        foreach (self::LOCALES as $locale) {
            $path = database_path("seeders/content/{$slug}.{$locale}.html");

            if (is_file($path)) {
                $bodies[$locale] = trim(file_get_contents($path));
            }
        }

        return $bodies;
    }
}
```

- [ ] **Step 4: Registra il seeder**

In `database/seeders/DatabaseSeeder.php`, dentro il primo `$this->call([...])` (quello che gira **sempre**, non il blocco demo), aggiungi in coda:

```php
            RoleSeeder::class,
            PageSeeder::class, // contenuto istituzionale: serve anche in produzione
```

- [ ] **Step 5: Esegui il test e verifica che passi**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=LegalContentTest'
```

Atteso: PASS, 7 test.

- [ ] **Step 6: Pint e commit**

```bash
vendor/bin/pint database/seeders tests/Feature/Content/LegalContentTest.php
git add database/seeders/PageSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/Content/LegalContentTest.php
git commit -m "feat(content): seed the terms pages from the versioned HTML files"
```

---

### Task 4: Rotte, componente, vista e tipografia

**Files:**
- Create: `app/Livewire/Content/LegalPage.php`
- Create: `resources/views/livewire/content/legal-page.blade.php`
- Create: `lang/it/legal.php`, `lang/en/legal.php`
- Modify: `lang/it/routes.php`, `lang/en/routes.php`
- Modify: `routes/web.php`
- Modify: `resources/css/app.css` (in coda)
- Modify: `tests/Feature/LangParityTest.php` (aggiungi `'legal'` alla lista)
- Test: `tests/Feature/Content/LegalPagesTest.php`

**Interfaces:**
- Consumes: `Page` (Task 1), `PageSeeder` (Task 3).
- Produces: rotte con nome `terms.customers` e `terms.suppliers`; componente `App\Livewire\Content\LegalPage` con proprietà pubblica `Page $page`.

- [ ] **Step 1: Scrivi il test che fallisce**

`tests/Feature/Content/LegalPagesTest.php`:

```php
<?php

namespace Tests\Feature\Content;

use App\Models\Page\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PageSeeder::class);
    }

    public function test_the_customer_terms_page_renders(): void
    {
        // assertSee con $escaped = false: il testo legale è pieno di apostrofi
        // tipografici, che nella pagina sono entità HTML.
        $this->get(route('terms.customers'))
            ->assertOk()
            ->assertSee('Termini e condizioni', false)
            ->assertSee('Definizioni', false);
    }

    public function test_the_supplier_terms_page_renders(): void
    {
        $this->get(route('terms.suppliers'))
            ->assertOk()
            ->assertSee('Premesse', false);
    }

    public function test_the_page_shows_the_last_update_date(): void
    {
        $this->get(route('terms.customers'))->assertSee('26/08/2026', false);
    }

    public function test_a_page_without_a_row_returns_404(): void
    {
        Page::where('slug', Page::TERMS_SUPPLIERS)->delete();

        $this->get(route('terms.suppliers'))->assertNotFound();
    }

    public function test_the_italian_route_has_no_locale_prefix_and_the_english_one_does(): void
    {
        // APP_LOCALE=it, quindi l'italiano è senza prefisso e l'inglese sotto /en.
        $this->assertStringEndsWith('/termini-e-condizioni', route('terms.customers'));

        $this->assertStringContainsString(
            '/en/terms-and-conditions',
            LaravelLocalization::getLocalizedURL('en', route('terms.customers')),
        );
    }
}
```

Il test importa `use Mcamara\LaravelLocalization\Facades\LaravelLocalization;`.
`route()` di Laravel non accetta un argomento di lingua: il prefisso lo mette
il gruppo di rotte, e per costruire l'URL in un'altra lingua serve l'helper di
mcamara.

- [ ] **Step 2: Esegui il test e verifica che fallisca**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=LegalPagesTest'
```

Atteso: FAIL con `Route [terms.customers] not defined`.

- [ ] **Step 3: Aggiungi gli slug localizzati**

In `lang/it/routes.php`, dopo `'contact' => 'contattaci',`:

```php
    'terms.customers' => 'termini-e-condizioni',
    'terms.suppliers' => 'termini-e-condizioni-fornitori',
```

In `lang/en/routes.php`, nella stessa posizione:

```php
    'terms.customers' => 'terms-and-conditions',
    'terms.suppliers' => 'supplier-terms-and-conditions',
```

- [ ] **Step 4: Aggiungi le etichette di contorno**

`lang/it/legal.php`:

```php
<?php

return [
    'last_updated' => 'Ultimo aggiornamento',
    // Mostrata solo fuori dall'italiano: i documenti sono redatti in italiano
    // ed è quella la versione che fa fede (clausola presente nei documenti stessi).
    'binding_language' => 'Traduzione di cortesia: fa fede esclusivamente la versione italiana.',
];
```

`lang/en/legal.php`:

```php
<?php

return [
    'last_updated' => 'Last updated',
    'binding_language' => 'Courtesy translation: only the Italian version is legally binding.',
];
```

Aggiungi `'legal',` alla lista in `tests/Feature/LangParityTest.php::fileProvider()`, in ordine alfabetico fra `'holiday'` e `'home'`... in realtà fra `'home'` e `'nav'`:

```php
                'home',
                'legal',
                'nav',
```

- [ ] **Step 5: Scrivi il componente**

`app/Livewire/Content/LegalPage.php`:

```php
<?php

namespace App\Livewire\Content;

use App\Models\Page\Page;
use Livewire\Component;

/**
 * Pagina legale servita da DB. Un solo componente per entrambe: lo slug arriva
 * dai defaults() della rotta, così aggiungere una pagina costa una riga in
 * web.php più il contenuto, non una classe nuova.
 */
class LegalPage extends Component
{
    public Page $page;

    public function mount(string $slug): void
    {
        $this->page = Page::where('slug', $slug)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.content.legal-page')->title($this->page->titleFor());
    }
}
```

- [ ] **Step 6: Aggiungi le rotte**

In `routes/web.php`, importa in cima (in ordine alfabetico fra gli altri `use App\Livewire\Content\...`):

```php
use App\Livewire\Content\LegalPage;
use App\Models\Page\Page;
```

e dentro il gruppo localizzato, subito dopo la rotta `community.post`:

```php
    // Pagine legali: stesso componente, distinte dallo slug del contenuto.
    Route::get(LaravelLocalization::transRoute('routes.terms.customers'), LegalPage::class)
        ->defaults('slug', Page::TERMS_CUSTOMERS)->name('terms.customers');
    Route::get(LaravelLocalization::transRoute('routes.terms.suppliers'), LegalPage::class)
        ->defaults('slug', Page::TERMS_SUPPLIERS)->name('terms.suppliers');
```

> Se il test di Step 8 fallisce con `Unable to resolve dependency [Parameter #0 [ <required> string $slug ]]`, Livewire 4 non legge i `defaults()` di rotta. Ripiego: togli i `defaults()`, dai a `LegalPage` un `mount(?string $slug = null)` che ricava lo slug dal nome della rotta corrente con una mappa costante `['terms.customers' => Page::TERMS_CUSTOMERS, 'terms.suppliers' => Page::TERMS_SUPPLIERS]` letta via `request()->route()->getName()`. Rotte, vista e test non cambiano.

- [ ] **Step 7: Scrivi la vista**

`resources/views/livewire/content/legal-page.blade.php`:

```blade
{{-- Pagina legale (termini e condizioni clienti/fornitori): corpo HTML dal DB, footer minimal come le altre pagine secondarie --}}
@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp

<div class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased">

    @include('partials.site-header')

    <main class="flex-1">
        <div class="{{ $px }} pt-[60px] pb-20 max-lg:pt-8 max-lg:pb-10">
            <div class="mx-auto w-full max-w-[900px]">
                <h1 class="text-4xl font-bold text-black max-lg:text-[18px] max-lg:leading-[21px] max-lg:text-[#0D171A]">
                    {{ $page->titleFor() }}
                </h1>

                @if ($page->last_updated_at)
                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('legal.last_updated') }}: {{ $page->last_updated_at->format('d/m/Y') }}
                    </p>
                @endif

                @if (app()->getLocale() !== \App\Models\Page\Page::SOURCE_LOCALE)
                    <p class="mt-4 rounded-[3px] bg-gray-100 px-4 py-3 text-sm text-[#2B2B2B]">
                        {{ __('legal.binding_language') }}
                    </p>
                @endif

                {{-- Corpo non escapato: HTML strutturato prodotto dal nostro seeder,
                     non input utente. Col CRUD di backoffice servirà sanitizzare. --}}
                <div class="legal-content mt-8 max-lg:mt-6">{!! $page->bodyFor() !!}</div>
            </div>
        </div>
    </main>

    @include('partials.footer-minimal')
</div>
```

- [ ] **Step 8: Aggiungi la tipografia**

In coda a `resources/css/app.css`:

```css
/* Corpo delle pagine legali: HTML dal DB, quindi senza classi Tailwind sui tag.
   Colonna di lettura stretta, gerarchia h2/h3, liste numerate annidate. */
.legal-content {
    color: #2b2b2b;
    font-size: 0.9375rem;
    line-height: 1.6;
}

.legal-content h2 {
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-ink);
}

.legal-content h3 {
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-ink);
}

.legal-content p {
    margin-bottom: 0.875rem;
}

.legal-content ol,
.legal-content ul {
    margin: 0 0 0.875rem 1.5rem;
}

.legal-content ol {
    list-style: decimal;
}

.legal-content ul {
    list-style: disc;
}

.legal-content li {
    margin-bottom: 0.375rem;
}

.legal-content a {
    color: var(--color-brand-cyan);
    text-decoration: underline;
}

.legal-content strong {
    font-weight: 700;
    color: var(--color-ink);
}
```

- [ ] **Step 9: Esegui i test e verifica che passino**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter="LegalPagesTest|LangParityTest"'
```

Atteso: PASS.

- [ ] **Step 10: Guarda la pagina davvero**

```bash
npm run build
```

Poi apri `http://animalamo.test/termini-e-condizioni` e `http://animalamo.test/termini-e-condizioni-fornitori`. Verifica: gerarchia dei titoli leggibile, liste rientrate, link cliccabili, niente riga orizzontale che sborda su mobile. Per la verifica con Playwright vale la skill `verify-in-browser` (le pagine Flux hanno trappole note sugli screenshot).

- [ ] **Step 11: Pint e commit**

```bash
vendor/bin/pint app/Livewire/Content/LegalPage.php routes/web.php lang tests
git add app/Livewire/Content/LegalPage.php resources/views/livewire/content/legal-page.blade.php \
        resources/css/app.css routes/web.php lang/it/legal.php lang/en/legal.php \
        lang/it/routes.php lang/en/routes.php tests/Feature/LangParityTest.php \
        tests/Feature/Content/LegalPagesTest.php
git commit -m "feat(content): publish the customer and supplier terms pages"
```

---

### Task 5: Versione inglese

**Files:**
- Create: `database/seeders/content/termini-e-condizioni.en.html`
- Create: `database/seeders/content/termini-e-condizioni-fornitori.en.html`
- Test: `tests/Feature/Content/LegalPagesTest.php` (aggiunta di casi)

**Interfaces:**
- Consumes: i file `*.it.html` (Task 2), il `PageSeeder` che già legge il locale `en` se il file esiste (Task 3).
- Produces: due file HTML con **la stessa identica struttura di tag e gli stessi `id`** dei corrispettivi italiani, contenuto tradotto.

Vincolo di lavorazione: la traduzione è di ~62.000 caratteri di testo contrattuale. Va fatta capitolo per capitolo, mantenendo intatti tag, attributi `id` e la numerazione delle clausole (`1.1`, `22.2.21`): cambia solo il testo. I termini definiti nei documenti (Utente/User, Fornitore/Supplier, Gestore/Operator, Piattaforma/Platform, Servizi/Services) vanno tradotti in modo coerente ovunque, perché sono definizioni contrattuali.

- [ ] **Step 1: Scrivi il test che fallisce**

Aggiungi a `tests/Feature/Content/LegalPagesTest.php`:

```php
    public function test_the_english_page_renders_the_english_body(): void
    {
        $this->get('/en/terms-and-conditions')
            ->assertOk()
            ->assertSee('Terms and conditions', false)
            ->assertSee('Definitions', false)
            ->assertDontSee('Sezione A', false);
    }

    public function test_the_english_page_warns_that_italian_prevails(): void
    {
        $this->get('/en/terms-and-conditions')
            ->assertSee('only the Italian version is legally binding', false);
    }
```

E a `tests/Feature/Content/LegalContentTest.php`:

```php
    #[DataProvider('fileProvider')]
    public function test_the_english_html_mirrors_the_italian_structure(string $slug, int $chapters): void
    {
        $italian = file_get_contents(database_path("seeders/content/{$slug}.it.html"));
        $english = file_get_contents(database_path("seeders/content/{$slug}.en.html"));

        // Stessa sequenza di tag: la traduzione cambia il testo, non la struttura.
        $this->assertSame(
            preg_replace('/>[^<]*</', '><', $italian),
            preg_replace('/>[^<]*</', '><', $english),
            "La struttura HTML di {$slug}.en.html non combacia con quella italiana",
        );
    }
```

> L'asserzione confronta anche gli `id` degli heading, che restano quelli
> italiani: sono ancore, non testo visibile, e tenerli uguali fa sì che un link
> a un capitolo funzioni in entrambe le lingue.

- [ ] **Step 2: Esegui i test e verifica che falliscano**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter="LegalPagesTest|LegalContentTest"'
```

Atteso: FAIL — `file_get_contents(...termini-e-condizioni.en.html): Failed to open stream`.

- [ ] **Step 3: Traduci il documento clienti**

Copia `termini-e-condizioni.it.html` in `termini-e-condizioni.en.html` e traduci il **solo testo** fra i tag, procedendo per blocchi (una `<h2>`/`<h3>` e i suoi paragrafi alla volta). Non toccare: nomi dei tag, attributi `id`, `href`, `target`, `rel`, i numeri di clausola.

- [ ] **Step 4: Traduci il documento fornitori**

Stessa procedura su `termini-e-condizioni-fornitori.en.html`. I 17 titoli di capitolo mantengono il numero: `1. Premesse` → `1. Recitals`.

- [ ] **Step 5: Esegui i test e verifica che passino**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter="LegalPagesTest|LegalContentTest"'
```

Atteso: PASS. Se il confronto di struttura fallisce, la diagnosi è quasi sempre un tag chiuso male durante la traduzione: confronta i due file con `diff <(sed 's/>[^<]*</></g' it) <(sed 's/>[^<]*</></g' en)`.

- [ ] **Step 6: Commit**

```bash
git add database/seeders/content/termini-e-condizioni.en.html \
        database/seeders/content/termini-e-condizioni-fornitori.en.html \
        tests/Feature/Content/LegalPagesTest.php tests/Feature/Content/LegalContentTest.php
git commit -m "feat(content): add the English translation of both terms documents"
```

---

### Task 6: Link nei footer

**Files:**
- Modify: `resources/views/partials/site-footer.blade.php:44`
- Modify: `resources/views/partials/partner-footer.blade.php:27`
- Test: `tests/Feature/Content/LegalPagesTest.php` (aggiunta di casi)

**Interfaces:**
- Consumes: le rotte `terms.customers` e `terms.suppliers` (Task 4).
- Produces: niente per i task successivi (ultimo task).

- [ ] **Step 1: Scrivi il test che fallisce**

Aggiungi a `tests/Feature/Content/LegalPagesTest.php`:

```php
    public function test_the_storefront_footer_links_to_the_customer_terms(): void
    {
        $this->get(route('home'))->assertSee(route('terms.customers'), false);
    }

    public function test_the_partner_footer_links_to_the_supplier_terms(): void
    {
        $this->get(route('partner.register'))->assertSee(route('terms.suppliers'), false);
    }
```

> Se `partner.register` non include `partner-footer`, cerca con
> `grep -rl "partner-footer" resources/views` una pagina pubblica che lo usa e
> punta il test su quella rotta.

- [ ] **Step 2: Esegui il test e verifica che fallisca**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=LegalPagesTest'
```

Atteso: FAIL, i footer hanno ancora `href="#"`.

- [ ] **Step 3: Collega i due link**

In `resources/views/partials/site-footer.blade.php`, riga 44:

```blade
        <a href="{{ route('terms.customers') }}" class="hover:text-brand-cyan">{{ __('nav.footer.terms') }}</a>
```

In `resources/views/partials/partner-footer.blade.php`, riga 27:

```blade
                    <li><a href="{{ route('terms.suppliers') }}" class="hover:text-brand-cyan">{{ __('partner.footer_terms') }}</a></li>
```

Le voci privacy, cookie policy e "gestisci cookie" restano `href="#"`: quei documenti non esistono.

- [ ] **Step 4: Esegui il test e verifica che passi**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test --filter=LegalPagesTest'
```

Atteso: PASS.

- [ ] **Step 5: Esegui la suite intera**

```bash
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test'
```

Atteso: verde. Se `LangParityTest` protesta, manca una chiave in `lang/en/legal.php`.

- [ ] **Step 6: Commit**

```bash
git add resources/views/partials/site-footer.blade.php resources/views/partials/partner-footer.blade.php \
        tests/Feature/Content/LegalPagesTest.php
git commit -m "feat(content): point the footer terms links at the new pages"
```

---

## Dopo il piano

- `php artisan migrate --seed` sugli ambienti dove il DB esiste già; in produzione basta `php artisan db:seed --class=PageSeeder` dopo la migrazione.
- `npm run build` prima del deploy: `.legal-content` è CSS nuovo.
- La traduzione inglese va fatta rileggere da un legale prima della messa online.
