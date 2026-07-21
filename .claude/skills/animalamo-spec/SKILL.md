---
name: animalamo-spec
description: "Functional-analysis knowledge base for the AnimalAmo platform — a multichannel (Web App + Mobile) pet-friendly travel/booking marketplace with a social + loyalty layer. Use when implementing or auditing any AnimalAmo feature: the B2C user module (4-step registration, smart search, points, 3-phase Stripe checkout, multi-product cart), the B2B partner module (10-step onboarding, subscription business model, inventory, cancellation policy, Smartbox), Community (Animal Network / Animal Times), Superadmin, the color system, the section rebrand map, or integrations (Stripe split payment, iCal, geolocation, Iubenda)."
allowed-tools:
  - Read
  - Grep
argument-hint: [module, feature, or topic — e.g. "checkout", "partner onboarding", "smartbox", "ch01"]
---

# AnimalAmo — Analisi Funzionale
**Project**: AnimalAmo | **Type**: functional-analysis spec | **Pages**: 4 | **Lang**: Italian | **Generated**: 2026-07-01

AnimalAmo is a multichannel (**Web App + Mobile App**) platform that centralizes the offer of **pet-friendly travel + services**. Users book stays, experiences, and specialist services; a **social** component enables user exchange, and a **points-based loyalty** system drives retention. Two-sided marketplace: **B2C users** ↔ **B2B partners**, governed by a **Superadmin**.

## How to Use This Skill

- **No args** — load the core frameworks + module/topic index below for reference while building.
- **A module/feature** (`checkout`, `ricerca`, `partner onboarding`, `smartbox`, `fidelizzazione`…) → I find the topic in the index and read the relevant chapter.
- **A chapter** (`ch01`/`ch02`/`ch03`) → I read that file.
- **Quick reference** → `cheatsheet.md` (colors, step counts, rebrand map, integrations).

This is the WHAT (scope + rules). It covers the 4-page functional analysis only — not code.

---

## Core Frameworks & Mental Models

**Two-sided marketplace, subscription-funded.** B2C users book; B2B partners supply. Revenue = **partner subscription** (mensile / semestrale / annuale). The commission model was **abandoned** — never reintroduce it.

**Semantic color system (Identità Cromatica).** Giallo = primary buttons · Azzurro = secondary buttons + chat · Magenta = tags + highlights. Treat colors as roles, not decoration.

**Fixed onboarding shapes.** User registration = **4 step** (ends in *Dati animale*). Partner onboarding = **10 step** (ends with mandatory *licenza di apertura* upload). Checkout = **3 fasi** (Dati → Stripe → Conferma).

**Rebrand map is canonical (use live names).** Holiday→**Vacanze**, Esperienze→**Attività e Servizi**, News→**Animal Times**, Community→**Animal Network**. Legacy names must not appear in new UI/code.

**Search never dead-ends.** Zero-result → propose **similar alternatives** (e.g. slightly higher price); map view + sort by distance (powered by geolocation).

**Unified multi-product cart.** Hotel + attività check out together in one 3-phase Stripe flow (with **split payment** where applicable).

**Explicit consent for Smartbox.** Partners must opt in before inclusion in promotional bundles.

**AI is assistive.** Auto-responder + first-line support only; escalate beyond that.

**Superadmin owns economic flows.** Only Superadmin sees contacts + economic flows + moderates content; partners never see global flows.

**Roadmap ≠ shipped.** iCal sync with Booking/Airbnb is an explicit **future evaluation** — don't assume it's present.

---

## Module Index (4 core)

| Module | Surface | Core capabilities |
|--------|---------|-------------------|
| **Utente (B2C)** | mobile/web end user | smart search (map + distance, zero-result fallback), scheda dettaglio, loyalty points, 3-phase Stripe checkout, multi-product cart |
| **Partner (B2B)** | partner dashboard | 10-step onboarding, inventory (rooms, check-in/out, Al completo/Disponibile), cancellation policy (30/15/7d/24h), bookings dashboard |
| **Community e Contenuti** | social + blog | Animal Network wall (tag posts), event discussions, Animal Times blog |
| **Superadmin** | full CMS | manage users/partners, moderate content+reviews, banners, DB + economic-flow monitoring |

## Chapter Index

| # | Title | Key topics |
|---|-------|-----------|
| [ch01](chapters/ch01-evoluzione-cronologica.md) | Evoluzione Cronologica del Progetto | 4 phases A–D, color system, registration/onboarding steps, business-model pivot, Smartbox, rebrand map, AI |
| [ch02](chapters/ch02-moduli-core.md) | Analisi Funzionale dei Moduli Core | Utente/Partner/Community/Superadmin modules, checkout, search fallback, cancellation windows |
| [ch03](chapters/ch03-tecnica-integrazioni.md) | Caratteristiche Tecniche e Integrazioni | Stripe split payment, iCal (roadmap), geolocation, license upload, Iubenda |

## Topic Index

- **AI / IA** → ch01
- **Animal Network / Animal Times** → ch01, ch02
- **Business model / subscription** → ch01
- **Cancellation policy** → ch02
- **Checkout / cart / Stripe** → ch02, ch03
- **Color system (Identità Cromatica)** → ch01
- **Community / social wall** → ch02
- **Fidelizzazione / points** → ch01, ch02
- **Geolocalizzazione** → ch03
- **iCal / Booking / Airbnb sync** → ch03
- **Inventory / availability** → ch02
- **Iubenda / privacy** → ch03
- **Licenza di apertura** → ch01, ch03
- **Onboarding partner (10 step)** → ch01
- **Rebrand map** → ch01, glossary
- **Registration (4 step)** → ch01
- **Ricerca Intelligente / fallback** → ch02
- **Smartbox** → ch01, ch02
- **Split payment** → ch03
- **Superadmin** → ch02
- **Trova il tuo professionista** → ch01

## Supporting Files

- [glossary.md](glossary.md) — all key terms (Italian names preserved) with definitions.
- [patterns.md](patterns.md) — recurring product/design techniques as decision guides.
- [cheatsheet.md](cheatsheet.md) — colors, step counts, rebrand map, integrations, hard rules.

---

## Scope & Limits

Covers the AnimalAmo functional-analysis document (4 pages) only. For implementation, combine with the project codebase. iCal Booking/Airbnb sync is roadmap, not committed scope. For payment specifics beyond "Stripe + split payment", consult Stripe docs.
