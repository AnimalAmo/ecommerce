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

**Se il file `x.b64` non compare su disco** (il server MCP può salvare in una cwd non raggiungibile): fallback via HTTP. Avvia un one-shot server in Bash (`&`, porta 8765) che decodifica il body base64 in `shot.png`, poi dal browser:

```js
await page.evaluate(async d => (await fetch('http://127.0.0.1:8765/', {method:'POST', headers:{'Content-Type':'text/plain'}, body: d})).text(), data)
```

(`Content-Type: text/plain` = simple request, niente preflight CORS; basta `Access-Control-Allow-Origin: *` nella risposta). Si può fare tutto in un solo `browser_run_code_unsafe`: CDP screenshot + POST, senza passare da `window.__shot`.

## Senza Playwright MCP: Chrome headless via CDP

Quando il server MCP non è in sessione, pilota Chrome a mano: `google-chrome --headless=new --remote-debugging-port=9334 --user-data-dir=/tmp/... about:blank`, poi un client websocket minimale (stdlib Python: handshake + frame mascherati) sul `webSocketDebuggerUrl`. Attenzione: da Chrome ≥111 `/json/new` vuole il metodo **PUT** (con GET sembra che Chrome non sia partito).

- **Viewport mobile**: `--window-size=375,812` NON basta, headless new impone un minimo di 500px e taglia lo screenshot facendo sembrare che il layout sfori. Usa `Emulation.setDeviceMetricsOverride {width:375,height:812,deviceScaleFactor:2,mobile:true}` + `Page.captureScreenshot {captureBeyondViewport:true}`.
- **Login** (le pagine profilo/partner lo richiedono, e il login è una modale, non una rotta GET): carica la home e chiama il componente Livewire via `Runtime.evaluate` con `awaitPromise: true`:
  ```js
  const c = Livewire.all().find(x => (x.name || '').includes('auth-modal'));
  await c.$wire.set('form.email', 'giulia.rossi@gmail.com');
  await c.$wire.set('form.password', 'password');
  await c.$wire.call('login');
  ```
  Il cookie di sessione resta nel profilo Chrome: le navigazioni successive sono autenticate.
- **Digitare negli iframe Stripe**: `Input.dispatchMouseEvent` (pressed+released) sulle coordinate CSS del campo, poi un `Input.dispatchKeyEvent {type:'char'}` per carattere. Il **primo** click dopo il mount viene spesso ingoiato: clicca due volte con ~1.5s di pausa prima di digitare, altrimenti i caratteri finiscono nel vuoto e l'element segna "numero incompleto".
- Le coordinate si leggono dallo screenshot ricordando il `deviceScaleFactor`: pixel dell'immagine ÷ 2 = px CSS da passare a CDP.

## Interazioni: trappole note

- **Dialog multipli**: i flyout Flux sono `<dialog>` sempre nel DOM. Scopa i locator per titolo: `page.locator('dialog:has-text("Titolo Modale")')`.
- **Testi duplicati nascosti**: bottoni/menu con lo stesso testo esistono in popover chiusi → `has-text` è case-insensitive e becca quelli invisibili. Usa `:text-is("Testo Esatto")` (case-sensitive) o scoping sul dialog visibile, e `[data-flux-menu]:visible` per i menu aperti.
- **Controlli custom element**: radio = `ui-radio`, switch = `ui-switch`, select listbox = `ui-select` + opzioni `ui-option[value=...]` (`getByRole('option')` matcha doppio: option nativa + ui-option).
- **Livewire è asincrono**: dopo click che fanno roundtrip aspetta `waitForTimeout(800-1500)` prima di asserire.
- Dopo `wire:navigate`/redirect il contesto Alpine riparte: ricontrolla lo stato, non fidarti dei riferimenti precedenti.

## Cosa verificare su una UI nuova (contro il mockup)

Allineamenti radio/label (Flux mette `mb-3` sui field dei gruppi: azzeralo), colori badge (default Flux battuti solo da classi `!`), uppercase/capitalize, icone nei badge/bottoni, spaziatura colonne tabella, toast che appare e scompare, flussi tra flyout (lo stato non deve perdersi nei passaggi).
