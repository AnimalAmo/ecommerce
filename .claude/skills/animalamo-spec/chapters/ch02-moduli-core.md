# Chapter 2: Analisi Funzionale dei Moduli Core

## Core Idea
Four core modules define the platform surface: **Utente (B2C)**, **Partner (B2B)**, **Community e Contenuti**, and **Superadmin**. Each has distinct capabilities; the Superadmin has total control including economic flows.

## The 4 Modules

### A — Modulo Utente (B2C)
End-user booking surface.
- **Ricerca Intelligente**: map view + sort by distance. **Zero-result fallback** → system proposes similar alternatives (e.g. structures at slightly higher price). No dead-end empty results.
- **Scheda Dettaglio**: photos, description, services **for humans and for animals** (e.g. welcome kit, ciotole/bowls), FAQ, reviews.
- **Fidelizzazione**: every purchase generates **points** → discounts or special perks.
- **Checkout — 3 fasi**: 1) Dati → 2) Pagamento via **Stripe** → 3) Conferma. Supports **multi-product cart** (hotel + attività in the same order).

### B — Modulo Partner (B2B)
Supplier operations.
- **Gestione Inventario**: room config (Suite, Superior, etc.), check-in/out times, availability state (**Al completo** / **Disponibile**).
- **Policy di Cancellazione**: partner-selectable options — **30, 15, 7 giorni, or 24 ore** before.
- **Dashboard Prenotazioni**: tabular request view with print + detail by type (**Vacanze, Eventi, Attività, Smartbox**).

### C — Community e Contenuti
Social + editorial layer.
- **Animal Network**: social wall where users post with **thematic tags** (e.g. benessere).
- **Discussioni di Evento**: each event has its own "Discussione" section for pre-participation questions.
- **Animal Times**: informational blog — normative, trasporti, pet-friendly tips.

### D — Modulo Superadmin
- **Controllo Totale**: manage users/partners, moderate content + reviews, insert advertising banners.
- **Database**: full access to contacts + economic flows for transaction monitoring.

## Module × Capability Matrix
| Module | Surface | Key capabilities | Notable rule |
|--------|---------|------------------|--------------|
| Utente (B2C) | mobile/web end user | smart search, detail, points, 3-phase checkout | zero-result → similar alternatives; multi-product cart |
| Partner (B2B) | partner dashboard | inventory, cancellation policy, bookings dashboard | availability = Al completo/Disponibile |
| Community | social + blog | Animal Network wall, event discussions, Animal Times | tag-based posts |
| Superadmin | full CMS | user/partner mgmt, moderation, banners, DB + economic flows | total control incl. transaction monitoring |

## Key Concepts
- **Zero-result fallback** — search never dead-ends; suggests similar (higher-price) options.
- **Multi-product cart** — hotel + attività checked out together.
- **Points (fidelizzazione)** — earned per purchase → discounts/perks.
- **Booking types** — Vacanze, Eventi, Attività, Smartbox (used in partner dashboard filtering).

## Anti-patterns
- **Returning an empty search** — must trigger the similar-alternatives fallback.
- **Separate carts per product type** — cart is unified/multi-product.
- **Giving partners visibility into global economic flows** — that's Superadmin-only.

## Key Takeaways
1. B2C checkout is 3 phases (Dati → Stripe → Conferma) with a unified multi-product cart.
2. Search sorts by distance on a map and never dead-ends (similar-alternative fallback).
3. Partner cancellation windows: 30 / 15 / 7 days / 24h.
4. Booking detail types = Vacanze, Eventi, Attività, Smartbox.
5. Superadmin alone sees economic flows + moderates all content.

## Connects To
- **Ch 1**: modules realize the phase-A/B UX + business decisions (points, subscription, Smartbox).
- **Ch 3**: checkout → Stripe split payment; search by distance → Geolocalizzazione.
