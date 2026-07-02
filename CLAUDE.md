# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

AnimalAmo ecommerce — pet-friendly travel/booking marketplace (B2C web app). Laravel 13 + Livewire 4 + Flux 2 + Tailwind CSS 4 + Vite 8. UI copy is Italian. The functional spec lives in the `animalamo-spec` skill; the homepage design spec is in `docs/specs/`.

## Commands

```bash
composer dev          # full dev stack: artisan serve + queue + pail logs + vite (concurrently)
npm run dev           # vite only (hot reload; usually enough when serving through the VM)
npm run build         # production assets — REQUIRED before deploy, new Tailwind classes don't exist in old builds
composer test         # config:clear + php artisan test (PHPUnit 12)
php artisan test --filter=SomeTest   # single test
vendor/bin/pint       # code style (Laravel Pint)
composer setup        # first-time install (env, key, migrate, npm, build)
```

### Local serving (this machine)

The app is served by a Homestead-style VM: `http://animalamo.test` → `192.168.56.56` (see `/etc/hosts`), MySQL on the VM (`DB_USERNAME=homestead`). `php artisan serve`/port 8000 on the host is NOT this app. Run `npm run dev` on the host — the browser loads assets from the host's vite (`public/hot`). First PHP request after a change can be slow (shared-folder view compilation); static assets respond instantly.

## Architecture

- **Pages are Livewire single-file components** in `resources/views/pages/*.blade.php`: an anonymous `new #[Title(...)] class extends Component` PHP block followed by the template. Routed in `routes/web.php` via `Route::livewire('/', 'pages::home')` — the `pages::` namespace maps to that folder.
- **Layout**: `resources/views/layouts/app.blade.php` (`$title`, `$slot`, `@livewireStyles`/`@fluxAppearance` in head).
- **Design tokens**: Tailwind 4 CSS-first config in `resources/css/app.css` under `@theme` — extracted from the Adobe XD file "Agg.Ecommerce AnimalAmo_22-02-24.xd" (artboard "Homepage – 4"). Use the tokens, not raw hex, when one exists: `brand-yellow` #EDFF00 (primary CTA), `brand-cyan` #6CD1EF, `brand-magenta` #FF3EA5, `brand-purple-soft` #C59FFD, `ink` #071825, `gray-150` #E9E9E9, `gray-100` #F4F4F4. Font: Nunito (loaded via bunny fonts in `vite.config.js`), weights 400/600/700/800.
- **Page container**: centered max-width, not full-width — pages define `@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp` and apply `{{ $px }}` to each section's inner wrapper (same scheme as the matsuri-nerd storefront). Full-bleed bands (hero photo, eventi band) stay edge-to-edge with `$px` on their content only. The XD canvas is 1920px with 140px margins; the 1600px cap replaces that for wide desktops.
- **Custom Flux icons**: `resources/views/flux/icon/*.blade.php` (`<flux:icon.pin>`, `<flux:icon.check-1>`, …), hand-converted from the XD-exported SVGs in `storage/Icone/`. Many still carry hardcoded `fill="#..."` on paths, which overrides `currentColor` and makes Tailwind text-color classes silently useless — strip the path-level fill when an icon must be tinted via class (already done for `pin`).
- **Design assets**: source photos from XD live in `storage/Immagini/` (committed); optimize before use into `public/img/` (`convert <src> -strip -interlace Plane -quality 82 public/img/<name>.jpg`). `design/` holds the PNG-per-artboard reference workflow (gitignored PNGs, see `design/README.md`).
- **Flux styling pattern**: brand overrides on Flux components use `!`-important utilities (e.g. `!rounded-full !bg-brand-yellow !text-ink`).

## Conventions

- Commit messages: Conventional Commits, English, no Co-Authored-By/AI-attribution trailers.
- Recurring XD spec details arrive as raw CSS snippets (border/radius/padding in px) — translate literally into arbitrary-value Tailwind classes (`rounded-[3px]`, `border-[#E9E9E9]`) unless a token matches exactly.
