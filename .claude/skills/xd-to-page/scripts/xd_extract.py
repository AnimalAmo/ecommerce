#!/usr/bin/env python3
"""Extract design specs from an Adobe XD file (.xd = zip of JSON).

Usage:
  xd_extract.py FILE.xd list                        # artboards: name + size
  xd_extract.py FILE.xd palette                     # named colors, swatches, character styles
  xd_extract.py FILE.xd dump "Artboard name"        # resolved node tree (symbols expanded)
  xd_extract.py FILE.xd dump "Artboard name" --raw  # raw JSON of the artboard (no symbol resolution)
  xd_extract.py FILE.xd images OUTDIR               # export embedded bitmap resources

Stdlib only. Artboard name match is case-insensitive prefix; pass the id too.
"""
import io
import json
import math
import re
import shutil
import sys
import zipfile
from pathlib import Path

MAX_DEPTH = 25


def hexcolor(v):
    if not isinstance(v, dict):
        return None
    try:
        return '#{:02X}{:02X}{:02X}'.format(v['r'], v['g'], v['b'])
    except (KeyError, TypeError, ValueError):
        return None


class XD:
    def __init__(self, path):
        self.zf = zipfile.ZipFile(path)
        self.manifest = json.loads(self.zf.read('manifest'))
        self.res = json.loads(self.zf.read('resources/graphics/graphicContent.agc'))
        ux = self.res.get('resources', {}).get('meta', {}).get('ux', {})
        self.ux = ux
        self.symbols = {}
        for s in ux.get('symbols', []):
            if s.get('id'):
                self.symbols[s['id']] = s
        # Un syncRef punta al singolo NODO dentro la definizione del symbol (o dentro
        # uno dei suoi `states`, le varianti del componente), non al symbol stesso:
        # cercarlo solo fra i symbol lascia gruppi vuoti nel dump (Tag, Indietro, …).
        self.nodes = {}
        for s in ux.get('symbols', []):
            self._index(s)

    def _index(self, node):
        if node.get('id'):
            self.nodes.setdefault(node['id'], node)
        for c in children_of(node):
            self._index(c)
        for state in node.get('meta', {}).get('ux', {}).get('states', []) or []:
            self._index(state)

    def artboards(self):
        for top in self.manifest.get('children', []):
            if top.get('name') != 'artwork':
                continue
            for a in top.get('children', []):
                if a.get('name') == 'pasteboard':
                    continue
                b = a.get('uxdesign#bounds', {})
                yield a['name'], a['path'], b.get('width'), b.get('height')

    def artboard_bounds(self, path):
        for top in self.manifest.get('children', []):
            if top.get('name') != 'artwork':
                continue
            for a in top.get('children', []):
                if a.get('path') == path:
                    return a.get('uxdesign#bounds', {})
        return {}

    def artboard_json(self, needle):
        needle = needle.lower()
        matches = [(n, p) for n, p, _, _ in self.artboards()
                   if n.lower().startswith(needle) or p == needle]
        if not matches:
            sys.exit(f'no artboard matching {needle!r} — run `list`')
        if len(matches) > 1 and not any(n.lower() == needle for n, _ in matches):
            print('ambiguous, matches:', ', '.join(repr(n) for n, _ in matches[:10]),
                  file=sys.stderr)
        name, path = matches[0]
        data = json.loads(self.zf.read(f'artwork/{path}/graphics/graphicContent.agc'))
        return name, data, self.artboard_bounds(path)


def children_of(node):
    return node.get('group', {}).get('children', []) or node.get('children', [])


def stroke_desc(st):
    """Stroke only when actually enabled: XD keeps a colour on disabled strokes
    (type 'none'), so printing it blindly invents borders that aren't there."""
    s = st.get('stroke') or {}
    if s.get('type') in (None, 'none'):
        return 'none'
    color = hexcolor(s.get('color', {}).get('value'))
    return f'{color}@{s.get("width")}'


def shape_box(sh):
    """Bounding box (x0, y0, x1, y1) of a shape, relative to its own node.

    Only `rect` carries width/height in the .agc: circles give r/cx/cy and paths give
    nothing but the SVG `d`. Measuring those from a PNG export is guesswork, so derive
    them here — the numbers in `d` are plain absolute coordinate pairs.
    """
    t = sh.get('type')
    if t == 'rect':
        return (sh.get('x', 0), sh.get('y', 0),
                sh.get('x', 0) + sh.get('width', 0), sh.get('y', 0) + sh.get('height', 0))
    if t in ('circle', 'ellipse'):
        rx = sh.get('rx', sh.get('r', 0))
        ry = sh.get('ry', sh.get('r', 0))
        cx, cy = sh.get('cx', 0), sh.get('cy', 0)
        return (cx - rx, cy - ry, cx + rx, cy + ry)
    if t in ('path', 'compoundPath'):
        nums = [float(n) for n in re.findall(r'-?\d+(?:\.\d+)?(?:[eE]-?\d+)?', sh.get('path') or '')]
        if len(nums) < 2:
            return None
        xs, ys = nums[0::2], nums[1::2]
        return (min(xs), min(ys), max(xs), max(ys))
    return None


def shadow_descs(st):
    """dropShadow filters as `shadow=dx,dy,blur,#RRGGBB@alpha` (CSS box-shadow order)."""
    out = []
    for f in st.get('filters') or []:
        if f.get('type') != 'dropShadow' or f.get('visible') is False:
            continue
        for d in f.get('params', {}).get('dropShadows', []):
            color = d.get('color', {}) or {}
            alpha = color.get('alpha', 1)
            out.append(f'shadow={d.get("dx")},{d.get("dy")},{d.get("r")},'
                       f'{hexcolor(color.get("value"))}@{alpha:.2f}')
    return out


def baseline_offset(text):
    """Offset della baseline della prima riga rispetto all'origine del nodo testo."""
    for para in text.get('paragraphs', []):
        for line in para.get('lines', []):
            for run in line:
                return run.get('y', 0)
    return 0


IDENTITY = (1.0, 0.0, 0.0, 1.0, 0.0, 0.0)


def mat_of(node):
    """Matrice locale del nodo come (a, b, c, d, tx, ty) — stessa convenzione di SVG."""
    t = node.get('transform', {}) or {}
    return (t.get('a', 1), t.get('b', 0), t.get('c', 0), t.get('d', 1),
            t.get('tx', 0), t.get('ty', 0))


def mat_mul(p, c):
    """p ∘ c: la trasformazione del figlio applicata dentro quella del padre."""
    pa, pb, pc, pd, ptx, pty = p
    ca, cb, cc, cd, ctx, cty = c
    return (pa * ca + pc * cb, pb * ca + pd * cb,
            pa * cc + pc * cd, pb * cc + pd * cd,
            pa * ctx + pc * cty + ptx, pb * ctx + pd * cty + pty)


def mat_apply(m, x, y):
    a, b, c, d, tx, ty = m
    return (a * x + c * y + tx, b * x + d * y + ty)


def mat_rotation(m):
    """Rotazione in gradi (senso orario, come CSS rotate): 0 se solo scala/traslazione."""
    return math.degrees(math.atan2(m[1], m[0]))


def mapped_box(m, box):
    """Bounding box RESA di una box locale: i 4 angoli passati per la matrice."""
    corners = [mat_apply(m, box[0], box[1]), mat_apply(m, box[2], box[1]),
               mat_apply(m, box[0], box[3]), mat_apply(m, box[2], box[3])]
    xs = [p[0] for p in corners]
    ys = [p[1] for p in corners]
    return (min(xs), min(ys), max(xs), max(ys))


def describe(node, xd, depth=0, pm=IDENTITY, out=None, seen=None):
    """Walk a node tree, expanding syncRef symbol instances, accumulating offsets."""
    if out is None:
        out, seen = [], set()
    if depth > MAX_DEPTH:
        return out
    if node.get('type') == 'syncRef':
        guid = node.get('syncSourceGuid')
        sym = xd.symbols.get(guid) or xd.nodes.get(guid)
        if sym and guid not in seen:
            seen.add(guid)
            describe(sym, xd, depth, pm, out, seen)
            seen.discard(guid)
        return out

    # Matrice accumulata (non solo gli offset): un'icona ruotata sta dove la
    # porta la sua a/b/c/d, non dove cade la sua tx/ty.
    m = mat_mul(pm, mat_of(node))
    x, y = m[4], m[5]
    rot = mat_rotation(m)
    st = node.get('style', {}) or {}
    bits = []
    typ = node.get('type', '?')
    name = node.get('name')
    if typ == 'text':
        txt = node.get('text', {})
        raw = txt.get('rawText', '').replace('\n', '\\n')
        f = st.get('font', {})
        fill = hexcolor(st.get('fill', {}).get('color', {}).get('value'))
        bits.append(f'TEXT {raw!r} font={f.get("postscriptName") or f.get("family")}'
                    f' size={f.get("size")} color={fill}')
        # L'origine del nodo NON è il bordo del testo: XD tiene la baseline della prima
        # riga in text.paragraphs[0].lines[0][0].y (0 per frame "positioned", ~size per
        # "autoHeight"). Senza questo si sbaglia lo spacing verticale di una riga intera.
        bits.append(f'baseline={y + baseline_offset(txt):.0f}')
    elif typ == 'shape':
        sh = node.get('shape', {})
        fill = hexcolor(st.get('fill', {}).get('color', {}).get('value'))
        r = sh.get('r')
        w, h = sh.get('width'), sh.get('height')
        box = shape_box(sh)
        if w is None and box:
            # path/circle non dichiarano width/height: senza queste misure un tondo o
            # un'icona restano "w=None" e si finisce a stimarle a occhio dal PNG.
            # Restano le misure INTRINSECHE (pre-rotazione): sono quelle da dare
            # all'elemento CSS, che poi si ruota di rot=.
            w, h = f'{box[2] - box[0]:.0f}', f'{box[3] - box[1]:.0f}'
        bits.append(f'SHAPE {sh.get("type")} w={w} h={h}'
                    f' fill={fill} stroke={stroke_desc(st)}'
                    + (f' radius={r}' if r else ''))
        # Un path parte quasi sempre a un offset dal proprio nodo: senza il box assoluto
        # la [x,y] della riga non è l'angolo di ciò che si vede. Con una rotazione vale
        # anche per i rect, e l'angolo va preso DOPO la matrice (mapped_box).
        if box and (sh.get('type') != 'rect' or abs(rot) >= 0.5):
            rb = mapped_box(m, box)
            bits.append(f'box=[{rb[0]:.0f},{rb[1]:.0f}]')
            if abs(rot) >= 0.5:
                # Elemento ruotato: box= è l'ingombro reso, w/h restano quelle da
                # dichiarare in CSS. Centra l'elemento nel box e applica rotate(rot).
                bits.append(f'rot={rot:.0f}deg rendered={rb[2] - rb[0]:.0f}x{rb[3] - rb[1]:.0f}')
    elif typ in ('group', 'artboard'):
        bits.append(f'GROUP {name!r}' if name else 'GROUP')
        pad = node.get('meta', {}).get('ux', {}).get('contentPadding')
        stack = node.get('meta', {}).get('ux', {}).get('contentStackType')
        if pad:
            bits.append(f'padding={pad}')
        if stack:
            bits.append(f'stack={stack}')
    else:
        bits.append(typ)
    opacity = st.get('opacity')
    if opacity is not None and opacity != 1:
        bits.append(f'opacity={opacity}')
    bits.extend(shadow_descs(st))
    out.append(f'{"  " * depth}[{x:.0f},{y:.0f}] ' + ' '.join(bits))
    for c in children_of(node):
        describe(c, xd, depth + 1, m, out, seen)
    return out


def cmd_list(xd):
    for name, path, w, h in xd.artboards():
        print(f'{w}x{h}  {name}  ({path})')


def cmd_palette(xd):
    print('== color swatches ==')
    for s in xd.ux.get('colorSwatches', []):
        print(' ', hexcolor(s.get('value')))
    print('== library colors (named) ==')
    for e in xd.ux.get('documentLibrary', {}).get('elements', []):
        if 'element.color' in e.get('type', ''):
            rep = e.get('representations', [{}])[0].get('content', {})
            print(f'  {hexcolor(rep.get("value"))}  {e.get("name") or ""}')
    print('== character styles ==')
    for e in xd.ux.get('documentLibrary', {}).get('elements', []):
        if 'characterstyle' in e.get('type', ''):
            c = e.get('representations', [{}])[0].get('content', {})
            print(f'  {c.get("fontFamily")} {c.get("fontStyle")} {c.get("fontSize")}px'
                  f' color={hexcolor(c.get("fontColor", {}).get("value"))}')


def cmd_dump(xd, needle, raw=False):
    name, data, bounds = xd.artboard_json(needle)
    print(f'== artboard: {name} ({bounds.get("width")}x{bounds.get("height")},'
          f' coords relative to artboard origin) ==')
    if raw:
        json.dump(data, sys.stdout, indent=1)
        return
    origin = (1.0, 0.0, 0.0, 1.0, -bounds.get('x', 0), -bounds.get('y', 0))
    for top in data.get('children', []):
        root = top.get('artboard', top)
        for c in root.get('children', []):
            for line in describe(c, xd, pm=origin):
                print(line)


def cmd_images(xd, outdir):
    out = Path(outdir)
    out.mkdir(parents=True, exist_ok=True)
    n = 0
    for info in xd.zf.infolist():
        p = info.filename
        if not p.startswith('resources/') or p.endswith('.agc') or '/' in p[10:]:
            continue
        blob = xd.zf.read(p)
        ext = {b'\x89PNG': '.png', b'\xff\xd8\xff': '.jpg', b'GIF8': '.gif',
               b'<svg': '.svg', b'RIFF': '.webp'}
        kind = next((e for m, e in ext.items() if blob[:4].startswith(m[:4])), '.bin')
        with open(out / (Path(p).name + kind), 'wb') as f:
            f.write(blob)
        n += 1
    print(f'{n} images -> {out}')


def main():
    if len(sys.argv) < 3:
        sys.exit(__doc__)
    xd = XD(sys.argv[1])
    cmd = sys.argv[2]
    if cmd == 'list':
        cmd_list(xd)
    elif cmd == 'palette':
        cmd_palette(xd)
    elif cmd == 'dump':
        cmd_dump(xd, sys.argv[3], raw='--raw' in sys.argv)
    elif cmd == 'images':
        cmd_images(xd, sys.argv[3])
    else:
        sys.exit(__doc__)


if __name__ == '__main__':
    main()
