# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

AnimalAmo ecommerce — pet-friendly travel/booking marketplace (B2C web app + B2B partner area). Laravel 13 + Livewire 4 + Flux 2 (Pro) + Tailwind CSS 4 + Vite 8. UI copy is Italian; routes are localized (mcamara/laravel-localization). The functional spec lives in the `animalamo-spec` skill; homepage design spec in `docs/specs/`.

## Non-negotiable rules

- **Flux over native elements**: never write a native `<button>` — always `flux:button` (or `flux:modal.trigger` / `flux:link as="button"`). Same for `flux:input`, `flux:badge`, `flux:separator`, … where a Flux equivalent exists; plain `<a>` is fine for pure text/nav links.
- **Design tokens, not raw hex**, when a token exists (see Architecture).
- **Services over fat components**: business logic lives in `app/Services/<Domain>Service.php`; Livewire components stay thin adapters (UI state + wiring).
- **Internal links use `route()`** — mcamara auto-prefixes the locale; hardcoded `href="/..."` silently drops it.
- **Commits**: Conventional Commits, English, no Co-Authored-By/AI-attribution trailers. Don't push unless asked.
- **`npm run build` before any deploy** — new Tailwind classes don't exist in old builds.
- **Never remove `@source` for flux-pro in `resources/css/app.css`** — without it Pro components render unstyled.
- **Tests run ONLY inside the VM** — host PHP is 8.2, app needs ≥8.3 (see Commands).

## Commands

```bash
composer dev          # full dev stack: artisan serve + queue + pail logs + vite (concurrently)
npm run dev           # vite only (hot reload; usually enough when serving through the VM)
npm run build         # production assets
vendor/bin/pint       # code style (Laravel Pint) — fine on the host
composer setup        # first-time install (env, key, migrate, npm, build)

# Tests — inside the Homestead VM only:
ssh vagrant@192.168.56.56 'cd /home/vagrant/Code/algomera/animal_amo/ecommerce && php artisan test'
#   single test: append --filter=SomeTest
```

### Local serving (this machine)

The app is served by a Homestead-style VM: `http://animalamo.test` → `192.168.56.56` (see `/etc/hosts`), MySQL on the VM (`DB_USERNAME=homestead`). `php artisan serve`/port 8000 on the host is NOT this app. Run `npm run dev` on the host — the browser loads assets from the host's vite (`public/hot`). First PHP request after a change can be slow (shared-folder view compilation); static assets respond instantly.

## Architecture

- **Pages/modals are class-based Livewire components** in `app/Livewire/`, grouped into domain subnamespaces: `Catalog/` (home, holiday, events, smartbox + details), `Commerce/` (Cart, CartBadge, Checkout, Favorites), `Profile/`, `Auth/` (the three login/register modals), `Content/` (News, Community, AboutUs), `Partner/` (B2B — hub `Dashboard`/`CreateService` at the root, plus wizard subnamespaces `Registration/`, `Structure/`, `Activity/`, `Smartbox/`). Each class has an explicit `render()` returning a view under the mirrored `resources/views/livewire/<group>/*.blade.php`. Routed in `routes/web.php` via `Route::get(LaravelLocalization::transRoute('routes.…'), ClassName::class)`. Tag-mounted components use the namespaced name (`<livewire:auth.auth-modal>`). Shared traits in `app/Livewire/Concerns/`, Form objects in `app/Livewire/Forms/`.
- **Payments**: Stripe only (card + Apple Pay + Google Pay). PayPal and Klarna were removed on client request (Jul 2026) — do not reintroduce.
- **Layout**: `resources/views/layouts/app.blade.php` (`$title`, `$slot`, `@livewireStyles`/`@fluxAppearance` in head).
- **Design tokens**: Tailwind 4 CSS-first config in `resources/css/app.css` under `@theme`: `brand-yellow` #EDFF00 (primary CTA), `brand-cyan` #6CD1EF, `brand-magenta` #FF3EA5, `brand-purple-soft` #C59FFD, `ink` #071825, `gray-150` #E9E9E9, `gray-100` #F4F4F4. Font: Nunito (bunny fonts via `vite.config.js`), weights 400/600/700/800.
- **Page container**: centered max-width, not full-width — pages define `@php $px = 'mx-auto w-full max-w-[1600px] px-4 lg:px-8'; @endphp` and apply `{{ $px }}` to each section's inner wrapper. Full-bleed bands keep `$px` on their content only. (XD canvas is 1920px with 140px margins; 1600px cap replaces that.)
- **Custom Flux icons**: `resources/views/flux/icon/*.blade.php` (`<flux:icon.pin>`, …). Strip hardcoded `fill="#..."` from paths or Tailwind text-color classes are silently useless.
- **Flux styling pattern**: brand overrides on Flux components use `!`-important utilities (e.g. `!rounded-full !bg-brand-yellow !text-ink`).

## Flux gotchas

- `flux:button` with `wire:click` wraps the slot in a `display:block` `<span>` (spinner swap) that stacks icon above label → add `[&>span]:flex [&>span]:items-center [&>span]:gap-2`.
- `flux:tabs` must ALWAYS sit inside `flux:tab.group`.
- **Flux components inherit the caller's scope for the props listed in their `@blaze(unsafe: [...])`** (`placeholder`, `name`, `label`, `invalid`, `size`, `description`, …). An anonymous component that declares `@props(['placeholder' => …])` and renders a `flux:select` inside leaks its own `$placeholder` into the select, which then emits a selected empty `<option>`. Rename your prop — passing `null` doesn't help, the fold resolves with `??=`.
- **`flux:select` (default variant) loses its chevron outside `flux:with-field`** — the arrow is drawn by the field wrapper, and the base classes include `appearance-none`. Add `!appearance-auto` when the select lives in your own container.
- **Never `Flux::modal(...)->show()` on a modal that a breakpoint hides.** `flux:modal` is a native `<dialog>`; `showModal()` blocks the whole document even when an ancestor is `display:none`, so a desktop-only modal opened from a mobile viewport leaves the page inert (nothing clickable, scroll locked) with no visible pop-up. PHP can't see the viewport — let the clicked control say which UI it wants (e.g. `openReview($id, asModal: false)` from the mobile pill).
- More traps (screenshots, selectors, custom elements) in the `verify-in-browser` skill.

## Project skills (.claude/skills/)

- `animalamo-spec` — functional spec knowledge base (modules, rebrand map, color roles, integrations).
- `xd-to-page` — implementing a page/section from the Adobe XD mockups (extraction script, tokens, photos, fidelity check).
- `verify-in-browser` — visual verification with Playwright MCP on Flux pages (known traps).
- `pre-commit-check` — canonical pre-commit sequence (pint → VM tests → build → commit format).
- `b2b-wizard-flow` — adding/changing partner wizard steps and draft→B2C publishing.
