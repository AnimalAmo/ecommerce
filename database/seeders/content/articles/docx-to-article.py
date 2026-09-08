#!/usr/bin/env python3
"""
Converte i .docx degli articoli Animal Times in HTML per l'ArticleSeeder e
ritaglia la foto dalla grafica social allegata al documento.

    python3 database/seeders/content/articles/docx-to-article.py

Sorgente: storage/articoli/*.docx (consegna cliente del 01/09/2026, fuori da git
perché sono i file originali). Uscite, entrambe versionate:

    database/seeders/content/articles/<slug>.it.html
    public/img/news/<slug>.jpg        card 960x495
    public/img/news/<slug>-hero.jpg   hero, ritaglio nativo

Struttura dei documenti: un paragrafo con il titolo, un unico paragrafo con
tutto il corpo diviso da <w:br>, e in coda l'immagine. Da qui le regole:

  * riga che comincia per "-" o "*"            -> <li> dentro <ul>;
  * riga corta senza punto finale              -> <h2>;
    (una riga che finisce con ":" è un <h2> solo se NON introduce una lista:
     "Nuovi standard dell'accoglienza:" è un titoletto, "Gli aspetti pratici
     da curare includono:" è l'attacco dell'elenco che segue)
  * tutto il resto                             -> <p>.

La prima riga del corpo, quando ripete il titolo (documenti 3 e 4), viene
scartata: il blade rende già il proprio <h1>.

L'immagine NON è una foto ma il post Instagram: foto + badge ANIMALTIMES +
titolo + "leggi l'articolo!". Si tiene solo la fascia sopra il badge, trovato
per colore nella metà bassa; il resto sarebbe titolo duplicato e una call to
action che sul sito non ha senso.

Nota: xml.etree della stdlib. Script one-shot su documenti forniti dal
committente, non un parser esposto a input non fidato.
"""

import html
import io
import os
import re
import zipfile
from xml.etree import ElementTree as ET

from PIL import Image

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'

ROOT = os.path.abspath(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', '..', '..'))
SOURCE_DIR = os.path.join(ROOT, 'storage', 'articoli')
HTML_DIR = os.path.join(ROOT, 'database', 'seeders', 'content', 'articles')
IMAGE_DIR = os.path.join(ROOT, 'public', 'img', 'news')

# Titolo e data non si ricavano dal .docx: il titolo è tutto maiuscolo (e in due
# documenti scritto "PET FRENDLY"), la data del file è quella in cui la cliente
# ha esportato il Word (01/09/2026), non quella di pubblicazione. La data vera è
# stampata in alto a sinistra sulla grafica social ("14mar2025", …).
ARTICLES = [
    {
        'docx': 'COME FAR DIVENTARE IL TUO B (1).docx',
        'slug': 'come-far-diventare-il-tuo-bb-un-alloggio-pet-friendly',
        'title': 'Come far diventare il tuo B&B un alloggio pet-friendly',
        'date': '2025-08-11',
    },
    {
        'docx': 'COME GESTIRE I BISOGNI DEL CUCCIOLO.docx',
        'slug': 'come-gestire-i-bisogni-del-cucciolo',
        'title': 'Come gestire i bisogni del cucciolo: consigli per una convivenza armoniosa',
        'date': '2025-07-18',
    },
    {
        'docx': 'COME MIGLIORARE L (1).docx',
        'slug': 'come-migliorare-accoglienza-animali-strutture',
        'title': 'Come migliorare l’accoglienza per gli animali: idee innovative per le strutture',
        'date': '2025-06-13',
    },
    {
        'docx': 'VIAGGIARE CON IL TUO ANIMALE (1).docx',
        'slug': 'viaggiare-con-il-tuo-animale',
        'title': 'Viaggiare con il tuo animale: consigli pratici per vacanze pet-friendly',
        'date': '2025-03-14',
    },
]

HEADING_MAX_LENGTH = 80
CARD_SIZE = (960, 495)

# La fascia alta del post porta la data ("14mar2025") e la zampa del logo: sul
# sito la data la scrive il blade e il logo sta in header, quindi si taglia.
TOP_MARGIN = 140


def lines(body):
    """Righe del corpo: i <w:br> separano, i paragrafi vuoti di Word spariscono."""
    out = []
    for paragraph in body.iter(W + 'p'):
        buffer = []
        for run in paragraph.iter(W + 'r'):
            for child in run:
                tag = child.tag.replace(W, '')
                if tag == 't':
                    buffer.append(child.text or '')
                elif tag == 'br':
                    buffer.append('\n')
        out.extend(part.strip() for part in ''.join(buffer).split('\n'))
    return [line for line in out if line]


def is_list_item(line):
    return line.startswith('- ') or line.startswith('* ')


def is_heading(line, following):
    if len(line) > HEADING_MAX_LENGTH or line.endswith(('.', '!', '?')):
        return False
    if line.endswith(':'):
        return not (following and is_list_item(following))
    return True


def repeats(line, title):
    """Confronto sui primi 20 caratteri normalizzati: i due documenti che
    riaprono con il titolo lo variano già dalla seconda metà della frase."""
    def key(text):
        return re.sub(r'[^a-z]', '', text.lower())[:20]

    return key(line) == key(title)


def to_html(rows, title):
    if rows and repeats(rows[0], title):
        rows = rows[1:]

    out = []
    open_list = False

    for index, line in enumerate(rows):
        following = rows[index + 1] if index + 1 < len(rows) else None

        if is_list_item(line):
            if not open_list:
                out.append('<ul>')
                open_list = True
            out.append('    <li>{}</li>'.format(html.escape(line[2:].strip())))
            continue

        if open_list:
            out.append('</ul>')
            open_list = False

        tag = 'h2' if is_heading(line, following) else 'p'
        out.append('<{0}>{1}</{0}>'.format(tag, html.escape(line.rstrip(':') if tag == 'h2' else line)))

    if open_list:
        out.append('</ul>')

    return '\n'.join(out) + '\n'


def crop_photo(archive, slug):
    """Ritaglia la fascia foto sopra il badge giallo del post Instagram."""
    image = Image.open(io.BytesIO(archive.read('word/media/image1.png'))).convert('RGB')
    width, height = image.size
    pixels = image.load()

    badge_top = height
    for y in range(int(height * 0.45), height):
        hits = 0
        for x in range(0, width, 4):
            r, g, b = pixels[x, y]
            if r > 210 and 150 < g < 215 and b < 80:
                hits += 1
        if hits > 15:
            badge_top = y
            break

    usable = badge_top - 12

    card_height = round(width / (CARD_SIZE[0] / CARD_SIZE[1]))
    top = max(TOP_MARGIN, TOP_MARGIN + (usable - TOP_MARGIN - card_height) // 2)
    image.crop((0, top, width, top + card_height)).resize(CARD_SIZE, Image.LANCZOS) \
        .save(os.path.join(IMAGE_DIR, f'{slug}.jpg'), quality=82, optimize=True)

    # Hero 620x451 CSS: il ritaglio nativo sta fra 1x e 2x, meglio di un
    # ingrandimento finto a 1240x902.
    hero_height = usable - TOP_MARGIN
    hero_width = min(width, round(hero_height * (620 / 451)))
    left = (width - hero_width) // 2
    image.crop((left, TOP_MARGIN, left + hero_width, usable)) \
        .save(os.path.join(IMAGE_DIR, f'{slug}-hero.jpg'), quality=82, optimize=True)


def main():
    for article in ARTICLES:
        archive = zipfile.ZipFile(os.path.join(SOURCE_DIR, article['docx']))
        body = ET.fromstring(archive.read('word/document.xml')).find(W + 'body')

        # La prima riga è il titolo del documento; to_html scarta poi anche
        # l'eventuale riga d'apertura che lo ripete.
        markup = to_html(lines(body)[1:], article['title'])
        path = os.path.join(HTML_DIR, f"{article['slug']}.it.html")
        with open(path, 'w', encoding='utf-8') as handle:
            handle.write(markup)

        crop_photo(archive, article['slug'])
        print(f"{article['slug']}: {len(markup.splitlines())} righe html, foto ok")


if __name__ == '__main__':
    main()
