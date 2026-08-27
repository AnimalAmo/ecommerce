#!/usr/bin/env python3
"""
Converte i testi redazionali in markdown (docs/privacy-*.md) in HTML semantico
per il PageSeeder. Fratello di docx-to-html.py, che fa lo stesso lavoro per i
documenti Word delle condizioni generali.

    python3 database/seeders/content/md-to-html.py docs/privacy-it.md \\
        database/seeders/content/privacy-policy.it.html

    python3 database/seeders/content/md-to-html.py docs/privacy-en.md \\
        database/seeders/content/privacy-policy.en.html \\
        --structure-from docs/privacy-it.md

Perché l'inglese ha bisogno di --structure-from: il file italiano marca gli
elenchi con '*', quello inglese li ha persi (righe nude, e l'ultimo item si
incolla al paragrafo successivo senza riga vuota). L'inglese non è quindi
convertibile da solo. Le due redazioni hanno però le stesse 210 righe non vuote
nello stesso ordine, quindi la struttura si proietta per posizione: ruolo di
ogni riga e id degli heading vengono dall'italiano, il testo dall'inglese.
Un'ancora a un capitolo vale così in entrambe le lingue.

Se una revisione futura sfasa i due file, il conteggio non torna e lo script
esce con errore invece di produrre HTML storto in silenzio.

Regole di struttura (dedotte dall'italiano, nessuna stringa hardcoded):
  * '1.' .. '18.'          -> <h2> (i capitoli)
  * 'A.' .. 'I.'           -> <h3> (le finalità del capitolo 3)
  * blocco di una riga senza punteggiatura finale, quando il blocco precedente
    non finisce con ':'                  -> <h3> (le etichette del capitolo 2)
    Il vincolo sul ':' distingue un'etichetta da un valore introdotto da due
    punti: 'Garante per la protezione dei dati personali' e l'indirizzo e-mail
    del capitolo 13 restano paragrafi.
  * '* voce'               -> <li> dentro <ul>
  * blocco di due righe con la prima corta e senza punteggiatura finale
                           -> <p><strong>prima</strong><br>seconda</p>
    (i fornitori del capitolo 7: DigitalOcean, Mailgun, Stripe)
  * ogni altro blocco      -> <p>, righe multiple unite da <br>

Cosa NON fa: inventare link. I testi non ne contengono — gli indirizzi e-mail
restano testo, come nei documenti sorgente.
"""

import argparse
import html
import re
import sys
import unicodedata

CHAPTER = re.compile(r'^\d{1,2}\.\s+\S')
LETTER = re.compile(r'^[A-Z]\.\s+\S')
BULLET = re.compile(r'^\*\s+')

# Oltre la quale una riga è una frase, non un'etichetta.
LABEL_MAX_LENGTH = 24
SENTENCE_ENDINGS = ('.', ':', ';', ',', '!', '?')


def blocks(path):
    """Il file spezzato in blocchi di righe consecutive non vuote."""
    with open(path, encoding='utf-8') as source:
        lines = [line.rstrip() for line in source]

    grouped, current = [], []
    for line in lines:
        if line.strip():
            current.append(line.strip())
        elif current:
            grouped.append(current)
            current = []
    if current:
        grouped.append(current)

    return grouped


def slugify(text):
    ascii_text = unicodedata.normalize('NFKD', text).encode('ascii', 'ignore').decode()
    slug = re.sub(r'[^a-z0-9]+', '-', ascii_text.lower()).strip('-')
    return slug[:60].strip('-') or 'sezione'


def roles(grouped):
    """Il ruolo di ogni riga, nell'ordine di lettura del file.

    Una lista di ('h2'|'h3'|'li'|'p'|'p-strong'|'p-cont', dati), una voce per
    riga non vuota: è questa sequenza che la versione tradotta riusa.
    """
    out = []
    previous_ends_with_colon = False

    for block in grouped:
        for index, line in enumerate(block):
            if BULLET.match(line):
                out.append(('li', BULLET.sub('', line)))
            elif CHAPTER.match(line):
                out.append(('h2', line))
            elif LETTER.match(line):
                out.append(('h3', line))
            elif (
                index == 0
                and len(block) == 1
                and not line.endswith(SENTENCE_ENDINGS)
                and not previous_ends_with_colon
            ):
                out.append(('h3', line))
            elif (
                index == 0
                and len(block) == 2
                and len(line) <= LABEL_MAX_LENGTH
                and not line.endswith(SENTENCE_ENDINGS)
            ):
                out.append(('p-strong', line))
            elif index == 0:
                out.append(('p', line))
            else:
                out.append(('p-cont', line))

        previous_ends_with_colon = block[-1].endswith(':')

    return out


def render(structure, texts):
    """structure: i ruoli (dall'originale). texts: le righe da rendere."""
    out, in_list, open_paragraph = [], False, False

    def close_paragraph():
        nonlocal open_paragraph
        if open_paragraph:
            out[-1] += '</p>'
            open_paragraph = False

    def close_list():
        nonlocal in_list
        if in_list:
            out.append('</ul>')
            in_list = False

    for (role, source), text in zip(structure, texts):
        escaped = html.escape(text)

        if role == 'p-cont':
            # Continuazione: si attacca al paragrafo aperto, non ne apre uno nuovo.
            out[-1] += f'<br>{escaped}'
            continue

        close_paragraph()

        if role == 'li':
            if not in_list:
                out.append('<ul>')
                in_list = True
            out.append(f'<li>{escaped}</li>')
            continue

        close_list()

        if role in ('h2', 'h3'):
            # L'id nasce sempre dal testo originale: le ancore devono valere
            # in tutte le lingue.
            out.append(f'<{role} id="{slugify(source)}">{escaped}</{role}>')
        elif role == 'p-strong':
            out.append(f'<p><strong>{escaped}</strong>')
            open_paragraph = True
        else:
            out.append(f'<p>{escaped}')
            open_paragraph = True

    close_paragraph()
    close_list()

    return '\n'.join(out) + '\n'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('source', help='markdown di partenza')
    parser.add_argument('destination', help='file HTML da scrivere')
    parser.add_argument(
        '--structure-from',
        metavar='ORIGINALE.md',
        help='markdown originale da cui prendere struttura e id (per le traduzioni)',
    )
    args = parser.parse_args()

    texts = [
        BULLET.sub('', line)
        for block in blocks(args.source)
        for line in block
    ]
    structure = roles(blocks(args.structure_from or args.source))

    if len(structure) != len(texts):
        sys.exit(
            f'Le due redazioni non si allineano: {args.structure_from} ha '
            f'{len(structure)} righe non vuote, {args.source} ne ha {len(texts)}. '
            'La traduzione deve seguire riga per riga la struttura '
            "dell'originale — controllare quale blocco è stato aggiunto o tolto."
        )

    with open(args.destination, 'w', encoding='utf-8') as out:
        out.write(render(structure, texts))

    counts = {}
    for role, _ in structure:
        counts[role] = counts.get(role, 0) + 1
    print(f'{args.destination}: ' + ', '.join(f'{n} {role}' for role, n in sorted(counts.items())))


if __name__ == '__main__':
    main()
