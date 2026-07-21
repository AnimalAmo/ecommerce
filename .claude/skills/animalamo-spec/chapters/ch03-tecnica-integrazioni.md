# Chapter 3: Caratteristiche Tecniche e Integrazioni

## Core Idea
Four technical pillars: **Stripe** payments (with split payment), **iCal** calendar sync (future), **geolocalizzazione**, and **security/privacy** (mandatory license upload + Iubenda). Some are shipped, one is explicitly future/roadmap.

## Integrations & Technical Features
- **Pagamenti — Stripe**: secure payments + **split payment** management (where applicable — "ove previsto"). Split payment is what routes funds partner-vs-platform.
- **Sincronizzazione Calendari — iCal**: **future evaluation** (not committed) to sync availability with **Booking** and **Airbnb**. Roadmap, not current scope.
- **Geolocalizzazione**: identify user position to filter results by proximity (feeds the map/distance sort in Ch 2).
- **Sicurezza & Privacy**:
  - **Mandatory upload of opening license (licenza di apertura)** for partners — hard gate in onboarding.
  - **Privacy via Iubenda**.

## Status Table (shipped vs roadmap)
| Feature | Integration | Status | Note |
|---------|-------------|--------|------|
| Payments | Stripe | core | includes split payment where applicable |
| Calendar sync | iCal ↔ Booking/Airbnb | **future/roadmap** | "valutazione futura" — do not assume present |
| Proximity filter | Geolocation | core | powers distance sort + map view |
| Partner license | file upload | core | mandatory gate in onboarding |
| Privacy/consent | Iubenda | core | cookie/privacy compliance |

## Key Concepts
- **Split payment** — Stripe feature dividing a payment between platform and partner.
- **iCal sync** — planned interoperability with Booking/Airbnb availability (not yet built).
- **Licenza di apertura** — legally-required partner document; upload is mandatory.
- **Iubenda** — third-party privacy/consent management provider.

## Anti-patterns
- **Treating iCal Booking/Airbnb sync as current scope** — it's explicitly a future evaluation.
- **Onboarding a partner without the opening-license upload** — it's mandatory.
- **Hand-rolling privacy/consent** — Iubenda is the chosen provider.

## Key Takeaways
1. Payments = Stripe, with split payment where applicable.
2. iCal ↔ Booking/Airbnb sync is roadmap, not shipped.
3. Geolocation drives the proximity filtering + distance sort.
4. Partner opening-license upload is a mandatory security gate.
5. Privacy/consent handled through Iubenda.

## Connects To
- **Ch 1**: subscription + Smartbox monetization → Stripe split payment; license upload appears in the 10-step onboarding.
- **Ch 2**: geolocation → smart search distance sort; Stripe → 3-phase checkout.
