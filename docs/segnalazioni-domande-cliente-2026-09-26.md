# Risposta alle segnalazioni del 29/09 — cosa è fatto e cosa serve

Bozza di mail per Isabella. Riferimento tecnico: `docs/plans/2026-09-26-segnalazioni-cliente-piano.md`.

---

Ciao Isabella,

grazie della mail, l'ho presa come lista di lavoro. Ti scrivo cosa è già sistemato e le poche cose su cui mi serve una tua parola per andare avanti.

## Già fatto

**1. Strutture senza sistema di pagamento.** Chi apre la scheda di una struttura che non ha collegato il pagamento non viene più portato al checkout. Al posto del box prenotazione trova i dati del partner e, se l'ha indicato nel suo profilo, il link al suo sito per prenotare. Vale per strutture, servizi, attività, eventi e smartbox. Gli eventi e le attività gratuite restano come prima, con il pulsante "Partecipa".

**2. Caratteristiche e servizi.** Sulla pagina pubblica compaiono ora solo le voci effettivamente selezionate. Le X rosse non ci sono più. Se una struttura non ha indicato nulla, il riquadro sparisce del tutto invece di restare vuoto con il solo titolo.

**3. Strutture, attività ed eventi.** Sistemato il caso peggiore: chi sceglieva "Servizi" — toelettatori, dog sitter, pet sitting — finiva nel percorso degli hotel e si vedeva chiedere se fosse un B&B o un agriturismo. Ora va direttamente nel percorso Attività. In più, passando da un percorso all'altro i campi di quello abbandonato restavano attaccati alla scheda e finivano pubblicati: adesso si ripuliscono da soli.

**6. Stripe.** Qui ho una notizia precisa, e la causa non è Stripe.

Il 22 settembre abbiamo aggiunto al sistema un contrassegno che marca le schede "pronte, in attesa del collegamento Stripe". Le schede completate **prima** di quella data non l'hanno mai ricevuto. Il risultato è che sono invisibili a tutto: alla procedura automatica che pubblica ogni dieci minuti, alla pagina "I miei servizi" del partner e al contatore della sua dashboard. Il partner ha compilato undici passaggi, ha collegato Stripe, e la scheda non esiste da nessuna parte. È esattamente quello che ti hanno segnalato.

C'era anche un secondo problema, più insidioso: la pagina "I miei servizi" scriveva "in attesa del collegamento Stripe" su **qualunque** scheda ferma, senza mai guardare Stripe. Chi era fermo per un altro motivo leggeva una diagnosi falsa, ci credeva, e ti apriva un ticket. Adesso il messaggio dice la causa vera: mancano dei dati, è in attesa di approvazione, sta andando online, oppure manca davvero Stripe.

Ho preparato uno strumento che elenca le schede ferme e le recupera. Per usarlo mi serve un accesso al server (vedi in fondo).

## Le cose su cui mi serve la tua risposta

Per ognuna ti dico cosa farei io, così se sei d'accordo basta che mi scrivi "ok" e vado.

**1. L'elenco delle tipologie di attività.** Nella mail ne nomini sette: toelettatore, asilo per cani, dog sitter, educatore cinofilo, fotografo pet, maneggio, fattoria didattica. Confermi che sono queste, più "Altro"? E si sceglie **una sola** tipologia, oppure più di una — per esempio un maneggio che è anche fattoria didattica?
→ *Se non mi dici niente: quelle sette più "Altro", scelta singola.* La lista va messa a database, e cambiarla dopo significa rimettere a posto i dati già inseriti: meglio decidere adesso.

**2. L'elenco delle tipologie di evento.** Chiedi il campo ma non elenchi le voci, e senza la lista non posso disegnare il menu a tendina. Mi serve da te.

**3. Le attività professionali devono ancora indicare una data?** Oggi il sistema **pretende** una data di inizio, altrimenti la scheda non si pubblica. Un dog sitter o un toelettatore non hanno una data: così com'è, non possono andare online. È un blocco vero.
→ *Cosa farei: data facoltativa per le attività, obbligatoria per gli eventi.* Dimmi però cosa vuoi che compaia sulla scheda al posto di "Data inizio / Data fine": gli orari di apertura? la zona in cui opera? niente?

**4. "Evento singolo o ricorrente": è un'etichetta o una ricorrenza vera?** Fa una differenza enorme. Se è un'etichetta descrittiva sulla scheda, è mezza giornata. Se il sistema deve generare davvero le date ripetute — ogni martedì, fino a dicembre — sono una settimana abbondante in più, perché oggi il sistema tiene una sola data per scheda e va rifatto.
→ *Cosa farei: partiamo dall'etichetta, la ricorrenza vera la valutiamo dopo.*

**5. "Posti limitati" e "prenotazione obbligatoria o facoltativa": cambiano il comportamento del sito o sono informazioni?** Se "posti limitati" è un **numero**, il sistema blocca davvero le prenotazioni oltre soglia e l'evento può esaurirsi; se è un sì/no, resta scritto sulla scheda e basta. Stessa domanda per la prenotazione obbligatoria.
→ *Cosa farei: posti limitati come numero che blocca davvero; prenotazione obbligatoria/facoltativa come sola informazione sulla scheda.*

**6. I contatti sulla scheda: quali, e di chi?** Qui devo essere chiaro su una cosa. **Telefono, email pubblica, sito e orari oggi non esistono da nessuna parte nel sistema.** L'unico telefono e l'unica email che abbiamo sono quelli che il partner usa per accedere e per la fatturazione: pubblicarli di nostra iniziativa non mi sembra corretto, e preferisco che la decisione sia tua e scritta.

Le strade sono due:
- **(a)** chiediamo al partner dei recapiti pubblici nuovi, con una spunta di consenso nel suo profilo. Più pulito, e lui sceglie cosa mostrare;
- **(b)** pubblichiamo quelli della registrazione.

→ *Cosa farei: la (a).* Dimmi anche quali voci vuoi: telefono, WhatsApp, email, sito, indirizzo, orari di apertura. Ogni voce in più è un campo in più da chiedere al partner.

Nel frattempo la scheda mostra ragione sociale, indirizzo e il link "dove pagare o prenotare": cose che il partner ha già dato sapendo che sarebbero state pubbliche.

**7. Il "checkout in struttura" va tolto o tenuto spento?** Esiste ancora una procedura che permette di **prenotare** online e pagare poi in struttura. Oggi dal sito non ci arriva più nessuno. Nella tua mail scrivi che il checkout deve comparire «quando è realmente previsto un pagamento **o una prenotazione online**»: se la prenotazione senza pagamento ti serve in futuro, quella procedura va tenuta; se non ti serve, la tolgo del tutto.
→ *Cosa farei: la tengo spenta.* Toglierla e poi rifarla costa il doppio.

## Due cose che non mi hai chiesto ma devi sapere

**Sette servizi del catalogo non sono selezionabili da nessuna parte.** Lavanderia, Ascensore, Noleggio bici, Dog sitter, Dog Beach nelle vicinanze, Supplemento animali, Piscina per cani: nessun passaggio del percorso partner permette di indicarli. Finora comparivano sempre con la X rossa; da adesso, giustamente, non compaiono mai. O li aggiungiamo al percorso, o li togliamo dal catalogo. Dimmi tu.

**Agli eventi gratuiti non si iscrive nessuno davvero.** Il pulsante "Partecipa" apre un messaggio di conferma e finisce lì: la partecipazione non viene registrata e non compare in "Eventi a cui partecipo". Non è una conseguenza di queste modifiche, è così da prima — ma adesso che la scheda ha perso il pulsante di acquisto si nota molto di più. Se vuoi che le iscrizioni vengano registrate davvero, è un lavoro a sé.

## Cosa mi serve dal server

Per confermare la diagnosi su Stripe e recuperare le schede ferme mi serve un accesso, o che qualcuno mi esegua quattro comandi. In particolare devo verificare tre cose che dal codice non si vedono:

1. se il processo automatico programmato (`schedule:run`) è davvero attivo in produzione — se non lo è, insieme alla pubblicazione delle schede si ferma anche l'invio dei bonifici ai partner;
2. se è acceso il filtro di approvazione preventiva delle schede: in quel caso una scheda può essere pubblicata e comunque invisibile, e il partner non ha modo di saperlo;
3. quanti segreti di collegamento Stripe sono configurati: ne servono due, e se ce n'è uno solo Stripe smette progressivamente di comunicare con noi.

Fammi sapere e procedo.
