#!/usr/bin/env python3
"""
Converte i .docx delle condizioni generali in HTML semantico per il PageSeeder.

    python3 database/seeders/content/docx-to-html.py <sorgente.docx> <destinazione.html> [--ids-from <file.it.html>]

Cosa fa (dialetto italiano, stili Titolo1/Titolo2/Paragrafoelenco):
  * Titolo1 -> <h2>, Titolo2 -> <h3>, con id ricavato dal testo;
  * nel documento fornitori i Paragrafoelenco di primo livello tutti in
    grassetto sono i capitoli: diventano <h3> numerati 1..N;
  * le liste numPr diventano <ol>/<ul> secondo il numFmt di numbering.xml;
  * gli hyperlink esterni diventano <a target="_blank" rel="noopener">;
  * il Sommario di Word viene scartato.

Cosa fa (dialetto inglese, riconosciuto da solo dallo stile Heading1 —
documenti già tradotti dal cliente, non prodotti da questo script):
  * Heading1 -> <h2> o <h3> a seconda che il testo cominci con "N.";
  * gli id NON si generano dal testo inglese: si riusano per posizione
    quelli del file italiano corrispondente, passato con --ids-from, così
    un'ancora a un capitolo vale in entrambe le lingue;
  * l'indice, in testa e con lo stesso stile del corpo, viene scartato fino
    alla seconda occorrenza del primo heading dell'indice;
  * ListBullet -> <li> dentro <ul>;
  * nessun auto-link: i documenti inglesi non ne contengono.

Cosa NON fa: toccare la numerazione delle clausole (1.1, 22.2.21), che nei
documenti è già testo letterale.

Nota: usa xml.etree della stdlib. È uno script one-shot che gira in locale sui
documenti forniti dal cliente, non un parser esposto a input non fidato; se un
giorno dovesse leggere docx di provenienza ignota, passare a defusedxml.
"""

import html
import re
import sys
import unicodedata
import zipfile
from urllib.parse import urlparse
from xml.etree import ElementTree as ET

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
R = '{http://schemas.openxmlformats.org/officeDocument/2006/relationships}'
TOC_STYLES = {'Titolosommario', 'Sommario1', 'Sommario2', 'Sommario3'}

# Il documento clienti nasconde, dietro le parole "Informativa sulla privacy e
# sui cookie" del punto 8.1, un link alla privacy policy di Booking.com — URL
# con gclid e ID affiliato, cioè copiato dalla barra del browser. La redazione
# inglese dello stesso punto non ha alcun link. Tolto su richiesta del
# committente: sparisce il collegamento, il testo resta.
UNLINKED_HOSTS = ('booking.com',)


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
            host = urlparse(target).hostname or ''
            if target.startswith('http') and not host.endswith(UNLINKED_HOSTS):
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


def heading_ids(path):
    """Gli id degli heading del file italiano, nell'ordine. La versione inglese
    li riusa per posizione: un'ancora a un capitolo deve valere in entrambe le
    lingue, quindi gli id restano quelli italiani anche in inglese."""
    with open(path, encoding='utf-8') as source:
        return re.findall(r'<h[23] id="([^"]+)">', source.read())


def is_english_dialect(body):
    """I documenti tradotti usano gli stili inglesi di Word (Heading1,
    ListBullet) invece di quelli italiani (Titolo1/Titolo2/Paragrafoelenco)."""
    return any(style_of(p) == 'Heading1' for p in body.iter(W + 'p'))


def convert_english(body, rels, ids):
    paragraphs = [p for p in body.iter(W + 'p') if inline_html(p, rels)]
    headings = [i for i, p in enumerate(paragraphs) if style_of(p) == 'Heading1']

    # L'indice è in testa e usa lo stesso stile del corpo: il corpo comincia
    # alla seconda occorrenza del primo heading dell'indice.
    first = plain(inline_html(paragraphs[headings[0]], rels))
    start = next(
        i for i in headings[1:]
        if plain(inline_html(paragraphs[i], rels)) == first
    )

    out, in_list, index, seen = [], False, 0, set()

    for paragraph in paragraphs[start:]:
        inner = inline_html(paragraph, rels)
        style = style_of(paragraph)
        text = plain(inner)

        # Il documento clienti chiude con l'elenco delle clausole da
        # approvare specificamente ex art. 1341 c.c., che cita alla lettera
        # il testo di capitoli già apparsi — ma Word applica loro lo stesso
        # stile Heading1 delle intestazioni vere. Un capitolo genuino compare
        # una sola volta dopo l'indice: un secondo Heading1 con testo già
        # visto è quella citazione, non una nuova sezione, e va trattato come
        # paragrafo normale (esattamente come nell'italiano, dove lo stesso
        # elenco non ha mai avuto stile di intestazione).
        if style == 'Heading1' and text in seen:
            style = None

        if style == 'Heading1':
            if in_list:
                out.append('</ul>')
                in_list = False
            if index >= len(ids):
                raise ValueError(
                    f'English document has {len(headings)} headings but only '
                    f'{len(ids)} ids are available from the Italian source '
                    f'(offending heading: "{text}")'
                )
            tag = 'h3' if re.match(r'\d+\.', text) else 'h2'
            out.append(f'<{tag} id="{ids[index]}">{text}</{tag}>')
            index += 1
            seen.add(text)
            continue

        if style == 'ListBullet':
            if not in_list:
                out.append('<ul>')
                in_list = True
            out.append(f'<li>{inner}</li>')
            continue

        if in_list:
            out.append('</ul>')
            in_list = False
        out.append(f'<p>{inner}</p>')

    if in_list:
        out.append('</ul>')

    return '\n'.join(out) + '\n'


def convert(source, ids=None):
    body, rels, formats = load(source)

    if is_english_dialect(body):
        if ids is None:
            raise ValueError(
                'English documents require --ids-from <file.it.html>: their '
                'headings reuse the Italian ids by position.'
            )
        return convert_english(body, rels, ids)

    out, stack, chapter = [], [], 0

    def close_lists():
        while stack:
            out.append(f'</{stack.pop()[0]}>')

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
            num_id, ilvl = numbering
            tag = 'ul' if formats.get(num_id) == 'bullet' else 'ol'

            # In entrambi i documenti un elenco è sempre piatto al suo interno
            # (l'ilvl non cambia mai tra un item e il successivo dello stesso
            # elenco): Word indenta talvolta l'intero elenco a un ilvl > 0
            # senza che esista alcun item al livello inferiore. La profondità
            # va quindi dedotta dalle variazioni di ilvl viste finora, non dal
            # suo valore assoluto, o un elenco piatto indentato diventerebbe
            # un <ul><ul>...</ul></ul> senza <li> di primo livello.
            while stack and stack[-1][1] > ilvl:
                out.append(f'</{stack.pop()[0]}>')

            if not stack or stack[-1][1] < ilvl:
                stack.append((tag, ilvl))
                out.append(f'<{tag}>')
            elif stack[-1][0] != tag:
                out.append(f'</{stack.pop()[0]}>')
                stack.append((tag, ilvl))
                out.append(f'<{tag}>')

            out.append(f'<li>{inner}</li>')
            continue

        close_lists()
        out.append(f'<p>{inner}</p>')

    close_lists()
    return '\n'.join(out) + '\n'


if __name__ == '__main__':
    args = sys.argv[1:]
    ids_path = None
    if '--ids-from' in args:
        flag_index = args.index('--ids-from')
        ids_path = args[flag_index + 1]
        del args[flag_index:flag_index + 2]

    if len(args) != 2:
        raise SystemExit(__doc__)

    source, destination = args
    converted = convert(source, heading_ids(ids_path) if ids_path else None)

    with open(destination, 'w', encoding='utf-8') as out:
        out.write(converted)
