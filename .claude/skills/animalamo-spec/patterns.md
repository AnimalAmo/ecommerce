# Patterns — AnimalAmo

Recurring design/product techniques from the functional analysis. Use as decision guides when building.

## Subscription-over-Commission Monetization
**When to use**: partner (B2B) revenue.
**How**: charge partners a recurring subscription (mensile / semestrale / annuale) instead of per-booking commission.
**Trade-offs**: predictable partner cost + competitive vs Booking/Airbnb; platform revenue decoupled from booking volume. Do NOT reintroduce commission logic — it was explicitly abandoned.

## Explicit-Consent Bundling (Smartbox)
**When to use**: including a partner in a promotional bundle.
**How**: require partner explicit opt-in before adding their structure to a Smartbox pacchetto.
**Trade-offs**: fewer auto-inclusions but avoids consent/legal issues; consent is a hard gate.

## Zero-Result Fallback Search
**When to use**: any user search that returns no matches.
**How**: instead of an empty state, propose similar alternatives (e.g. structures at slightly higher price); sort by distance on a map.
**Trade-offs**: never dead-ends the user; requires a similarity/relaxation strategy on price/location.

## Unified Multi-Product Cart
**When to use**: checkout with mixed items.
**How**: one cart holds hotel + attività together; single 3-phase checkout (Dati → Stripe → Conferma).
**Trade-offs**: simpler UX; checkout + payment must handle heterogeneous line items and split payment.

## Loyalty Points (Fidelizzazione)
**When to use**: rewarding purchases.
**How**: every acquisto generates points → redeemable for sconti / vantaggi speciali.
**Trade-offs**: retention lever; needs a ledger + redemption rules.

## Staged Rebrand with Legacy Map
**When to use**: renaming sections late in development.
**How**: keep an explicit old→new map — Holiday→Vacanze, News→Animal Times, Community→Animal Network, Esperienze→Attività e Servizi.
**Trade-offs**: prevents stale names in UI/code; requires a single source of truth for names.

## SEO-First Content Launch
**When to use**: pre-launch indexing.
**How**: ship the blog (Animal Times) before the booking platform so content indexes early.
**Trade-offs**: earlier organic reach; content must exist before core product.

## Assistive AI (First-Line Responder)
**When to use**: customer support.
**How**: AI as automatic responder + first-line request handling; escalate beyond that.
**Trade-offs**: deflects volume; keep AI assistive, not autonomous decisioning.

## Mandatory Compliance Gate
**When to use**: partner onboarding.
**How**: require opening-license (licenza di apertura) upload; handle privacy via Iubenda.
**Trade-offs**: legal safety; adds friction to the 10-step onboarding.
