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
    if len(sys.argv) != 3:
        raise SystemExit(__doc__)

    with open(sys.argv[2], 'w', encoding='utf-8') as destination:
        destination.write(convert(sys.argv[1]))
