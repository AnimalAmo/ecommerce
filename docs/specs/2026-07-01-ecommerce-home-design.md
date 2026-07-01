# AnimalAmo Ecommerce — build spec (living)

**Date:** 2026-07-01 · **Source design:** `../Agg.Ecommerce AnimalAmo_22-02-24.xd` (58 desktop artboards, 1920px web)

## Stack (decided)
- **Laravel 13.18** on **PHP 8.3** (Laravel 13 needs PHP ≥8.3; system default `php` is 8.2 — use `php8.3`).
- **Livewire 4.3** — single-file / page components. Pages live in `resources/views/pages/`, routed via `Route::livewire('/', 'pages::home')`.
- **Tailwind v4** (`@tailwindcss/vite`) + **Nunito** (bunny fonts). Brand tokens in `resources/css/app.css @theme`.
- Repo root = `./ecommerce`. Remote: `git@github.com:algomeraIT/animal_amo.git` (push only on request).

## Environment caveats
- **No DB driver** on php8.3 (`pdo_sqlite`/`pdo_mysql` absent, no passwordless sudo to add). Home + static pages need none. Before any DB-backed page, user installs e.g. `php8.3-sqlite3` (or `-mysql`) via sudo.
- Drivers set DB-free: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.
- Missing `ext-curl`/`ext-bcmath` on php8.3 — composer run with `--ignore-platform-req` for both (not needed at runtime yet).
- Run: `php8.3 artisan serve` · `npm run dev|build`.

## Fidelity approach (decided)
Symbol-instances (`syncRef`) make full auto-layout reconstruction from XD JSON unreliable. So:
- **Visual truth = user-exported PNG per page** → `design/<page>.png` (gitignored). Desktop-only pixel match (no responsive yet).
- **Reliable from JSON** (already extracted): text strings, colors, fonts, grid metrics, raster assets.
- Per page: build Livewire page section-by-section → `php8.3 artisan serve` → screenshot at 1920 via headless chromium → diff vs PNG → iterate.

## Design tokens (extracted)
- Font **Nunito** (Bold 700 titles @36px, Regular body @18px).
- Yellow `#EDFF00` (primary btn) / soft `#E9FF7D` · Cyan `#6CD1EF` (secondary/chat) · hero bg `#EBF9FD` · Magenta `#FF3EA5` (tags) · Purple `#8E53E6` · Ink `#071825`. Grays 100–600.
- Grid: 1920 canvas, 140px side margins, 12 cols, 16 gutter.

## Home (`Homepage – 4`, 1920×5510) — section order (from JSON)
1. **Hero** — title + subtitle + search (Dove / Quando).
2. **Animal Holiday** — 3 region cards (N Strutture · "Hotel e servizi in Liguria/Veneto/Trentino-AA").
3. **Eventi pet friendly** — event cards (luogo, data, titolo, "Gratis" tag) + "Scopri gli eventi".
4. **Acquista una Smartbox** — "Trova il regalo giusto".
5. **News** — article cards (data, titolo) .
6. **Community** — post preview + "Scopri la community".
7. Footer.
Body copy in the design is "Lorem ipsum" placeholder.

## Page queue (58 artboards)
Home → Animal Holiday (list/regione/dettaglio) → Eventi → Smartbox → Carrello → Checkout 1–5 → Profilo(+sub) → Community → News → Preferiti → auth pop-ups → …

## Status
- ✅ Project scaffolded, stack verified (HTTP 200, Livewire page renders, Nunito + palette applied).
- ✅ Home **shell** live at `/` (placeholder header/hero/sections/footer).
- ⏳ Waiting on `design/home.png` to build the real Home.
