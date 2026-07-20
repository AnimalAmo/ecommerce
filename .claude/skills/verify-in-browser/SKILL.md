---
name: verify-in-browser
description: Quando serve verificare visualmente nel browser una UI Livewire/Flux di AnimalAmo (http://animalamo.test) con Playwright MCP — screenshot, click su modali/dropdown, confronto col mockup. Necessaria perché su pagine Flux browser_take_screenshot va in timeout e i selettori Flux hanno trappole note.
---

# Verifica browser di UI Flux/Livewire con Playwright MCP

L'app gira su `http://animalamo.test` (VM Homestead). Assicurati che `npm run dev` sia attivo sull'host, altrimenti gli asset non caricano.

## Screenshot: browser_take_screenshot NON funziona su pagine Flux

I bottoni Flux tengono spinner `animate-spin` nascosti (wire:loading) sempre animati: la stability-wait di Playwright non converge e ogni screenshot va in timeout, anche con `animations: 'disabled'`. Workaround CDP in 3 passi:

1. `browser_run_code_unsafe`:
   ```js
   async (page) => {
     const cdp = await page.context().newCDPSession(page);
     const { data } = await cdp.send('Page.captureScreenshot', { format: 'png' });
     await page.evaluate(d => { window.__shot = d; }, data);
     return 'ok ' + data.length;
   }
   ```
   (niente `require`/`import` nel sandbox: non puoi scrivere file da lì)
2. `browser_evaluate` con `function: () => window.__shot` e `filename: 'x.b64'` → il file finisce nella cwd del repo.
3. `tr -d '"\n' < x.b64 | base64 -d > shot.png && rm x.b64` e leggi il png.

Attenzione: `window.__shot` muore a ogni navigazione — fai il dump subito. Se il risultato è la stringa `undefined` la pagina è stata ricaricata: rifai lo screenshot.

## Interazioni: trappole note

- **Dialog multipli**: i flyout Flux sono `<dialog>` sempre nel DOM. Scopa i locator per titolo: `page.locator('dialog:has-text("Titolo Modale")')`.
- **Testi duplicati nascosti**: bottoni/menu con lo stesso testo esistono in popover chiusi → `has-text` è case-insensitive e becca quelli invisibili. Usa `:text-is("Testo Esatto")` (case-sensitive) o scoping sul dialog visibile, e `[data-flux-menu]:visible` per i menu aperti.
- **Controlli custom element**: radio = `ui-radio`, switch = `ui-switch`, select listbox = `ui-select` + opzioni `ui-option[value=...]` (`getByRole('option')` matcha doppio: option nativa + ui-option).
- **Livewire è asincrono**: dopo click che fanno roundtrip aspetta `waitForTimeout(800-1500)` prima di asserire.
- Dopo `wire:navigate`/redirect il contesto Alpine riparte: ricontrolla lo stato, non fidarti dei riferimenti precedenti.

## Cosa verificare su una UI nuova (contro il mockup)

Allineamenti radio/label (Flux mette `mb-3` sui field dei gruppi: azzeralo), colori badge (default Flux battuti solo da classi `!`), uppercase/capitalize, icone nei badge/bottoni, spaziatura colonne tabella, toast che appare e scompare, flussi tra flyout (lo stato non deve perdersi nei passaggi).
