# Articoli di Animal Times (4 pezzi della cliente) + widget Iubenda

Data: 2026-09-08

## Problema

La sezione Animal Times andava online con **sei articoli finti**: titoli e foto
presi dall'artboard XD, corpo vuoto, date del 2023 (`News::ARTICLES`, costante
del componente). La home ne mostrava altri tre, mock a loro volta, e uno di
questi (`event-cavallo`) non aveva neppure un dettaglio dove atterrare.

Il 08/09/2026 la cliente ha consegnato quattro articoli veri in
`storage/articoli/*.docx`. Nello stesso giro va inserito lo script del widget
Iubenda (privacy e cookie), che il sito non aveva.

## Cosa contengono i .docx

Struttura identica nei quattro: un paragrafo col titolo tutto maiuscolo, un
unico paragrafo con tutto il corpo diviso da `<w:br>`, in coda una PNG
1080x1350. Niente stili Word, niente liste `numPr`, niente link.

Due scoperte che decidono il resto:

1. **L'immagine non è una foto**: è il post Instagram — foto, badge
   ANIMALTIMES, titolo ripetuto, "leggi l'articolo!" e, in alto a sinistra,
   **la data di pubblicazione stampata** (`14mar2025`, `13giu2025`,
   `18lug2025`, `11ago2025`).
2. **La data del file non è la data dell'articolo**: `docProps/core.xml` dice
   01/09/2026 per tutti e quattro, cioè quando la cliente ha esportato i Word.
   Le date vere sono solo quelle sulle grafiche.

## Decisioni prese

1. **I sei articoli campione spariscono.** Erano segnaposto d'artboard: online
   sarebbero notizie inventate. Restano i quattro veri, e con loro la home.
2. **Stesso impianto delle pagine legali.** Tabella `articles`, model
   `Article` con `HasTranslations`, `ArticleSeeder` che legge HTML versionato
   da `database/seeders/content/articles/`. Git è la fonte, il DB la sorgente
   a runtime. Nessun secondo modo di fare contenuti redazionali.
3. **Niente colonna immagine né colonna occhiello.** Le foto si chiamano come
   lo slug (`public/img/news/<slug>.jpg` e `-hero.jpg`) e le produce lo stesso
   script che converte i testi; l'occhiello è il primo paragrafo del corpo
   troncato a 200 caratteri. Due dati in meno da tenere allineati a mano.
4. **Titoli riscritti in tondo.** Nei .docx sono tutti maiuscoli e due dicono
   "PET FRENDLY"; l'XD li vuole in tondo (`Nuove normative strutture pet
   friendly`). Refuso corretto, maiuscolo sciolto, resto invariato — sta nel
   seeder, non nell'HTML, perché è metadato e non testo.
5. **Foto ritagliata sopra il badge.** Del post social si tiene solo la fascia
   fotografica: il badge trova il proprio giallo nella metà bassa
   dell'immagine, e sopra si scarta anche la prima fascia (140px) che porta
   data e zampa del logo. Restano una card 960x495 e una hero nativa (~880x640,
   fra 1x e 2x dei 620x451 dell'XD: meglio di un ingrandimento finto).
6. **Solo italiano.** La cliente non ha consegnato le traduzioni: il seeder
   scrive la sola chiave `it` e il model ripiega sull'italiano quando manca
   l'inglese, come fa `Page`.
7. **"Carica altro" ora fa qualcosa** — sei articoli per pagina, `loadMore()`
   ne aggiunge sei — **e sparisce quando non c'è altro da caricare**: con
   quattro articoli era un bottone morto in pagina.
8. **Iubenda in `<head>`, prima degli altri script.** È il punto in cui può
   bloccare i cookie non essenziali finché il consenso non c'è; il widget di
   Tidio resta dov'era, in fondo al body. La presenza dello script è coperta
   da un test: senza informativa il sito non deve poter andare online per
   distrazione.

## Conversione .docx → HTML

`database/seeders/content/articles/docx-to-article.py`, fratello dei due
convertitori delle pagine legali. Nessuno stile Word da leggere, quindi le
regole guardano la forma della riga:

* riga che comincia per `-` o `*` → `<li>` dentro `<ul>`;
* riga corta senza punto finale → `<h2>`;
* riga che finisce con `:` → `<h2>` **solo se non introduce un elenco**
  ("Nuovi standard dell'accoglienza:" è un titoletto, "Gli aspetti pratici da
  curare includono:" è l'attacco della lista che segue);
* tutto il resto → `<p>`.

La prima riga del corpo, quando ripete il titolo (documenti 3 e 4), viene
scartata: il blade rende già il proprio `<h1>`.

I `.docx` restano fuori da git, come i sorgenti dei termini: sono binari da
2-3 MB e il diff utile è quello dell'HTML.

## Cosa resta aperto

* **Traduzioni inglesi**: quattro file `<slug>.en.html` da aggiungere quando
  la cliente li manda. Nessun'altra modifica: il seeder li carica da solo.
* **Un articolo finisce a metà frase.** "Viaggiare con il tuo animale" chiude
  con "…per farlo sentire al sicuro", senza punto: è così nel .docx. Da
  chiedere alla cliente.
* **Le foto XD dei vecchi articoli** (`public/img/xd/news-*.jpg`, 11 file) non
  le usa più nessuno.
* **Backoffice**: quando arriverà, `updateOrCreate` va cambiato in
  `firstOrCreate` (stessa nota del `PageSeeder`), altrimenti un `db:seed`
  cancella le modifiche fatte dalla cliente.
