# Chapter 1: Evoluzione Cronologica del Progetto

## Core Idea
AnimalAmo evolved through 4 sequenced phases (A→D): first the B2C visual identity + user flows, then the B2B partner side + a pivot from commission to subscription, then mobile + AI, then final consolidation + rebranding. Naming and business-model decisions here are load-bearing for everything downstream.

## The 4 Phases (preserve these exact labels)

### A — Definizione UI/UX e Flussi B2C
Established visual identity and Home Page structure.
- **Identità Cromatica** (color system, canonical): **Giallo** predominant = primary buttons; **Azzurro** (light blue) = secondary buttons + chat; **Magenta** = tags + highlight points.
- **Registrazione Utente (4 Step)**: 1) Dati personali → 2) Cellulare e password → 3) Indirizzo → 4) Dati dell'animale domestico. (Pet data is a required registration step, not optional.)
- **Architettura delle Sezioni**: originally split into *Animal Holiday* (hotels) and *Esperienze* (services). *Esperienze* was **renamed "Attività e Servizi"** to avoid confusion with user stories/racconti.

### B — Sviluppo Lato Partner (B2B) e Business Model
Focus shifts to supplier management + monetization.
- **Onboarding Partner**: a **10-step** process to enter structures — room types, prices, cancellation policy, upload of opening licenses (licenze di apertura).
- **Cambio Modello di Business** (the pivot): from **commission-based → subscription** (mensile / semestrale / annuale) for partners, to stay competitive vs large portals (Booking/Airbnb).
- **Logica Smartbox**: promotional bundles where partners must give **explicit opt-in consent** to be included.

### C — Ottimizzazione Mobile e Intelligenza Artificiale
App interface + role of AI.
- **User Experience App**: main slider, eased search, bottom nav menu — **Esplora, Preferiti, Community, Carrello, Profilo**.
- **IA come Assistenza**: AI configured as an **automatic responder** for customer support + first-line request handling (assistive, not autonomous decisioning).
- **Ricerca Avanzata**: filters by date (single or range), lodging type, and detailed animal spec (cani/gatti).

### D — Consolidamento e Rebranding
Pre-release refinement. **Final section rename map**:
- Holiday → **Vacanze**
- News → **Animal Times**
- Community → **Animal Network**
- **Strategia SEO**: launch *Animal Times* (blog) early to index content before the booking platform goes live.
- **"Trova il tuo professionista"**: dedicated module to search trainers (addestratori) + pet-sitter.

## Key Concepts
- **Smartbox** — promotional bundle requiring partner explicit consent.
- **Subscription pivot** — the defining monetization decision (replaced commissions).
- **Rebrand map** — Vacanze / Animal Times / Animal Network are the live names; Holiday / News / Community are legacy.

## Anti-patterns
- **Using legacy section names** (Holiday, News, Community, Esperienze) in new UI/code — always use the rebranded names.
- **Modeling revenue as commission** — the model is subscription; commission is abandoned history.
- **Auto-including partners in Smartbox** — consent is explicit and required.

## Key Takeaways
1. Colors are semantic: giallo=primary, azzurro=secondary/chat, magenta=tags/highlights.
2. Registration is 4 steps ending in pet data; onboarding partner is 10 steps.
3. Monetization = partner subscription (monthly/semi-annual/annual), not commission.
4. Live section names: Vacanze, Attività e Servizi, Animal Times, Animal Network.
5. AI = assistive auto-responder for support, first line only.

## Connects To
- **Ch 2**: these phase decisions become the Modulo Utente / Partner requirements.
- **Ch 3**: subscription + Smartbox tie to Stripe split payment; licenses tie to Sicurezza.
