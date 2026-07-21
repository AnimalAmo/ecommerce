---
name: xd-to-page
description: Quando si implementa una pagina, sezione o componente AnimalAmo partendo da un mockup Adobe XD e servono le specifiche esatte — colori, font, dimensioni, spacing, testi, foto. Copre estrazione dall'.xd (che è uno zip di JSON, leggibile senza Adobe XD), mappatura sui token del progetto, ottimizzazione immagini e verifica di fedeltà col PNG.
---

# Da mockup XD a pagina Livewire/Flux

## Dove sono i file XD

Nella cartella sopra la repo (`../` rispetto a `ecommerce/`). Ogni `.xd` è già estratto come directory doppio-annidata — il file leggibile dallo script è:

```
../Agg.Ecommerce AnimalAmo_22-02-24.xd/Agg.Ecommerce AnimalAmo_22-02-24.xd   # B2C ecommerce
../Agg.Partner AnimalAmo_05-02-24.xd/Agg.Partner AnimalAmo_05-02-24.xd       # B2B partner
../Agg.App Animal_Amo - 22.04.24.xd/Agg.App Animal_Amo - 22.04.24.xd         # mobile app
```

Mai stimare colori/misure da uno screenshot quando c'è l'.xd: hex, font, px, radius e testi sono tutti estraibili.

## Script di estrazione

```bash
XD="../Agg.Ecommerce AnimalAmo_22-02-24.xd/Agg.Ecommerce AnimalAmo_22-02-24.xd"
python3 .claude/skills/xd-to-page/scripts/xd_extract.py "$XD" list                 # artboard: WxH + nome + id
python3 .claude/skills/xd-to-page/scripts/xd_extract.py "$XD" palette              # colori documento + character styles
python3 .claude/skills/xd-to-page/scripts/xd_extract.py "$XD" dump "Nome Artboard" # albero nodi risolto
python3 .claude/skills/xd-to-page/scripts/xd_extract.py "$XD" images OUTDIR        # bitmap embedded
```

Solo stdlib, nessuna dipendenza. Il nome artboard è un prefix match case-insensitive; funziona anche l'id. `dump --raw` per il JSON grezzo se il riassunto non basta.

Output di `dump`: ogni riga ha `[x,y]` relativi all'artboard, poi `TEXT 'contenuto' font=… size=… color=#… baseline=…`, `SHAPE rect w= h= fill=… stroke=… radius=…`, o `GROUP` (con `padding=`/`stack=` se il designer ha usato layout content-aware). I symbol (`syncRef`) sono auto-espansi.

- **Sui TEXT usa `baseline=`, non la `y`**: la `y` è l'origine del nodo, che per i frame `positioned` coincide con la baseline ma per gli `autoHeight` sta una riga più in alto. Confrontare `y` fra due testi di frame diverso sbaglia lo spacing di ~1 line-height. Da baseline a margini CSS: con `leading-none` il bordo superiore del testo sta a `baseline − size`, quello inferiore a `baseline + 0.25·size` (Nunito).

- **`box=[x,y]` sui non-rect**: `path`, `circle` ed `ellipse` non dichiarano `width`/`height` nell'.agc — lo script le ricava (dal `d` SVG o da `r`/`cx`/`cy`) e stampa anche l'angolo reale del disegno, che quasi mai coincide con la `[x,y]` del nodo. Il tondo giallo del banner Community è `[168,197] SHAPE path w=40 h=40`, il `+` dentro è `w=15 h=15 box=[180,209]` (nodo a `[174,203]`). Usa `box=` per gli allineamenti, non la coordinata del nodo.

- `stroke=none` = bordo **disattivato** (XD conserva il colore di uno stroke spento): niente `border` nel markup. Con il bordo attivo esce `stroke=#RRGGBB@spessore`.
- `shadow=dx,dy,blur,#RRGGBB@alpha` (ordine CSS `box-shadow`) compare quando il nodo ha un dropShadow: `shadow=0,0,5.5,#000000@0.11` → `shadow-[0px_0px_6px_#0000001C]`. Un pannello "con contorno colorato" spesso è in realtà bianco con ombra.

## Workflow

1. `list` → trova l'artboard della pagina da costruire.
2. `dump "<nome>"` → skeleton del layout; `palette` → mappa i colori sui **token esistenti** in `resources/css/app.css` (`@theme`) prima di inventarne di nuovi.
3. **Coordinate**: il canvas è 1920px con margini 140px; il progetto usa il container `max-w-[1600px]` (vedi CLAUDE.md). Mappa proporzioni e spacing, non pixel assoluti.
4. **Foto**: le sorgenti full-res estratte dall'.xd sono già in `storage/Immagini/` (committate). Ottimizza prima dell'uso:
   ```bash
   convert storage/Immagini/<src> -strip -interlace Plane -quality 82 public/img/<name>.jpg
   ```
5. **Icone**: SVG esportati in `storage/Icone/`; convertili a mano in `resources/views/flux/icon/<nome>.blade.php` (`<flux:icon.nome>`). Rimuovi i `fill="#..."` hardcoded sui path, altrimenti le classi Tailwind di colore non hanno effetto.
6. Costruisci il componente Livewire + Flux seguendo le regole del CLAUDE.md (Flux-only, token, container `$px`).
7. **Misura, non guardare**: la verifica più veloce non è confrontare due PNG a occhio ma leggere i `getBoundingClientRect()` nel browser e sottrarli alle coordinate dell'artboard, ancorando tutto a un elemento (es. l'`h1`) perché l'header reale non è alto come quello dell'XD. Uno scarto costante su tutta la colonna = un solo margine sbagliato; scarti che crescono = un'altezza sbagliata che si accumula.
8. **Verifica fedeltà**: se esiste un PNG di riferimento in `design/<pagina>.png` (export 1x dell'artboard, gitignorato — vedi `design/README.md`), confronta visivamente con la skill `verify-in-browser`. Le spec extra dal cliente arrivano come snippet CSS in px: traducili letteralmente in classi arbitrarie (`rounded-[3px]`, `border-[#E9E9E9]`) salvo token esatto.

## Formato .xd (se lo script non basta)

| Path nello zip/directory | Contenuto |
|---|---|
| `manifest` | nomi/id artboard, `uxdesign#bounds` (coordinate assolute pasteboard) |
| `artwork/artboard-<id>/graphics/graphicContent.agc` | albero nodi di un artboard |
| `resources/graphics/graphicContent.agc` | colori nominati, character styles, definizioni symbol |
| `resources/<hash>` | bitmap senza estensione (riconosci dai magic bytes) |
| `interactions/interactions.json` | link/transizioni di prototipo |

Nodi: `transform.tx/.ty` = offset dal parent (accumula per l'assoluto); `style.fill.color.value` = `{r,g,b}`; testo in `text.rawText`; `type: "syncRef"` → cerca `syncSourceGuid` nei symbol globali.

## Errori comuni

| Errore | Realtà |
|---|---|
| Colori a occhio dallo screenshot | `palette` + `dump` danno gli hex esatti |
| Aspettarsi PNG per-artboard dentro l'.xd | c'è solo `preview.png` di UN artboard; il layout viene dal JSON |
| Coordinate dump usate come CSS | sono design coords a 1920px — mappa proporzioni |
| Aprire lo script sul path `.xd` esterno | il file vero è quello doppio-annidato `X.xd/X.xd` (per l'App è un file zip, per gli altri una directory — lo script legge entrambi) |
| Gruppo vuoto nel `dump` (es. campi input) | è un `syncRef` non risolto: la definizione sta in `resources/graphics/graphicContent.agc` sotto `resources.meta.ux.symbols[]` — cerca l'`id` del syncRef lì dentro |
| Stimare a occhio la misura di un tondo o di un'icona | `dump` ora dà `w=`/`h=` anche per path e circle, più il `box=` con l'angolo vero |
| Fidarsi del `fill` sulle icone-linea (es. X delle chip con fill arancio #FF9F3E) | i path aperti tipo `ion-close-outline` rendono solo lo `stroke`: il fill è un residuo invisibile in XD — usa il colore di stroke |
| Disegnare un bordo colorato perché il dump mostra uno stroke | se la riga dice `stroke=none` il bordo è spento: quel colore è un residuo. Le "voci con contorno viola" del menu Profilo app sono bianche **senza bordo**, con `shadow=0,0,5.5,#000000@0.11` |
