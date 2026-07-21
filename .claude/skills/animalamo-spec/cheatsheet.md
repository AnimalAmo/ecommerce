# AnimalAmo — Cheatsheet

## Color system (Identità Cromatica)
| Color | Use |
|-------|-----|
| **Giallo** (yellow) | primary buttons |
| **Azzurro** (light blue) | secondary buttons + chat |
| **Magenta** | tags + highlight points |

## Step counts
- **User registration**: 4 step → Dati personali · Cellulare+password · Indirizzo · Dati animale
- **Partner onboarding**: 10 step → room types, prezzi, cancellation policy, license upload
- **Checkout**: 3 fasi → Dati · Pagamento (Stripe) · Conferma

## Rebrand map (legacy → live)
| Legacy | Live |
|--------|------|
| Animal Holiday / Holiday | **Vacanze** |
| Esperienze | **Attività e Servizi** |
| News | **Animal Times** (blog) |
| Community | **Animal Network** (social wall) |

## Bottom nav (App)
Esplora · Preferiti · Community · Carrello · Profilo

## Business model
Partner **subscription** (mensile / semestrale / annuale) — NOT commission.

## Cancellation windows (partner-selectable)
30 giorni · 15 giorni · 7 giorni · 24 ore

## Booking types (partner dashboard)
Vacanze · Eventi · Attività · Smartbox

## Availability states
Al completo · Disponibile

## Integrations
| Concern | Tool | Status |
|---------|------|--------|
| Payments | Stripe (+ split payment) | core |
| Calendar sync | iCal ↔ Booking/Airbnb | **roadmap** |
| Proximity | Geolocation | core |
| Privacy/consent | Iubenda | core |
| Partner license | mandatory upload | core |

## The 4 core modules
Utente (B2C) · Partner (B2B) · Community e Contenuti · Superadmin

## Hard rules
- Search never dead-ends → similar-alternatives fallback.
- Multi-product cart (hotel + attività together).
- Smartbox inclusion needs partner explicit consent.
- Partner opening-license upload is mandatory.
- AI = assistive first-line responder only.
