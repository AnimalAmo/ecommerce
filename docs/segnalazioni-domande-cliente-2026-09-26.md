# Risposta alle segnalazioni della cliente — cosa è fatto e cosa serve

Bozza di mail per Isabella. Riferimento tecnico: `docs/plans/2026-09-26-segnalazioni-cliente-piano.md`.
Revisione del 26/09/2026: aggiunti lo stato dei punti 4 e 5, il limite reale del punto 1, la
parte del punto 3 che resta aperta, le due domande su smartbox e carrelli in corso.

---

Ciao Isabella,

grazie della mail, l'ho presa come lista di lavoro. Ti scrivo cosa è già sistemato, cosa resta,
e le poche cose su cui mi serve una tua parola per andare avanti.

## Già fatto

**1. Strutture senza sistema di pagamento.** Chi apre la **scheda** di una struttura che non ha
collegato il pagamento non trova più il pulsante di acquisto e non viene portato al checkout. Al
posto del box prenotazione trova i dati del partner e, se l'ha indicato nel suo profilo, il link al
suo sito per prenotare. Vale per strutture, servizi, attività, eventi e smartbox. Gli eventi e le
attività gratuite restano come prima, con il pulsante "Partecipa".

Due residui, e preferisco elencarli io: **sulle liste** — la pagina Eventi e la pagina di una regione —
la card mostra ancora "Aggiungi al carrello" anche per questi partner, perché lì la modalità di
pagamento non viene letta (costerebbe una lettura per ogni riquadro). E chi aveva già messo un loro
prodotto nel carrello **prima** della modifica arriva ancora alla vecchia procedura. In nessuno dei due
casi il cliente paga qualcosa online: finisce nel percorso "paghi in struttura". Ma la scheda dice una
cosa e la lista un'altra, quindi va chiuso — vedi la domanda 8.

**2. Caratteristiche e servizi.** Sulla pagina pubblica compaiono ora solo le voci effettivamente
selezionate. Le X rosse non ci sono più. Se una struttura non ha indicato nulla, il riquadro
sparisce del tutto invece di restare vuoto con il solo titolo.

**3. Strutture, attività ed eventi — la parte peggiore.** Chi sceglieva "Servizi" — toelettatori,
dog sitter, pet sitting — finiva nel percorso degli hotel e si vedeva chiedere se fosse un B&B o un
agriturismo. Ora va direttamente nel percorso Attività. In più, passando da un percorso all'altro i
campi di quello abbandonato restavano attaccati alla scheda e finivano pubblicati: adesso si
ripuliscono da soli.

Resta però aperto il caso che descrivi tu, e preferisco dirlo chiaro: **la scelta fatta durante la
registrazione oggi viene dimenticata.** Il partner la indica, il sistema la valida, e poi lo porta
in dashboard senza portarsela dietro: quando crea il primo servizio deve scegliere di nuovo, e lì
può ancora finire nel percorso sbagliato. È mezza giornata di lavoro e la faccio.

Una cosa che non mi torna e su cui mi serve il tuo aiuto: **in registrazione le voci sono tre —
struttura, attività, servizi — e "Evento" non c'è.** Gli eventi si scelgono più avanti, dentro il
percorso Attività. Dimmi dove hai visto "Evento" in registrazione, o se preferisci che lo aggiunga
come quarta voce: è la soluzione più chiara, e a quel punto il percorso si differenzia da subito.

**6. Stripe.** Qui ho una notizia precisa, e la causa non è Stripe.

Il 22 settembre abbiamo aggiunto al sistema un contrassegno che marca le schede "pronte, in attesa
del collegamento Stripe". Le schede completate **prima** di quella data non l'hanno mai ricevuto. Il
risultato è che sono invisibili a tutto: alla procedura automatica che pubblica ogni dieci minuti,
alla pagina "I miei servizi" del partner e al contatore della sua dashboard. Il partner ha compilato
undici passaggi, ha collegato Stripe, e la scheda non esiste da nessuna parte. È esattamente quello
che ti hanno segnalato.

C'era anche un secondo problema, più insidioso: la pagina "I miei servizi" scriveva "in attesa del
collegamento Stripe" su **qualunque** scheda ferma, senza mai guardare Stripe. Chi era fermo per un
altro motivo leggeva una diagnosi falsa, ci credeva, e ti apriva un ticket. Adesso il messaggio dice
la causa vera: mancano dei dati, è in attesa di approvazione, sta andando online, oppure manca
davvero Stripe.

Ho preparato uno strumento che elenca le schede ferme e le recupera. Per usarlo mi serve un accesso
al server (vedi in fondo). Ti chiedo di non annunciare ancora ai partner che è risolto: restano due
cause possibili che dal codice non posso escludere — una scheda può risultare pubblicata e comunque
non comparire sulle pagine della sua regione, se la provincia inserita nel percorso non è stata
riconosciuta. Lo verifico con i comandi sul server e poi ti confermo caso per caso.

## Non ancora fatto: i punti 4 e 5

Su questi non ho scritto una riga, e non per dimenticanza: ogni campo nuovo finisce a database e
cambiarlo dopo significa rimettere a posto i dati già inseriti. Aspetto le risposte qui sotto.

- **Punto 4, attività e professionisti.** Togliere punto d'incontro e orari è semplice. Ma
  *tipologia di attività*, *contatti* e *possibilità di prenotazione* oggi non esistono come campi, e
  vanno aggiunti al percorso partner, al pannello di amministrazione e alla scheda pubblica. Conto
  **3–4 giornate.**
- **Punto 5, eventi.** Mancano tipologia, "singolo o ricorrente" e "prenotazione obbligatoria o
  facoltativa". "Posti limitati" invece esiste già nel sistema, è solo disattivato. Conto **2,5–3
  giornate**, se "ricorrente" resta un'etichetta (vedi domanda 4).
- Più **1–2 giornate** per chiudere quello che resta dei punti 1, 3 e 6: la scelta in registrazione,
  i contatti veri, i residui sulla pubblicazione.

Quindi una decina di giornate, appena arrivano le risposte. Le due domande che possono farle
crescere sono la 4 (ricorrenza vera: una settimana in più) e la 6 (contatti per singola struttura
invece che per partner: 3–4 giorni in più).

## Le cose su cui mi serve la tua risposta

Per ognuna ti dico cosa farei io, così se sei d'accordo basta che mi scrivi "ok" e vado.

**1. L'elenco delle tipologie di attività.** Nella mail ne nomini sette: toelettatore, asilo per
cani, dog sitter, educatore cinofilo, fotografo pet, maneggio, fattoria didattica. Confermi che sono
queste, più "Altro"? E si sceglie **una sola** tipologia, oppure più di una — per esempio un maneggio
che è anche fattoria didattica?
→ *Se non mi dici niente: quelle sette più "Altro", scelta singola.* La lista va messa a database, e
cambiarla dopo significa rimettere a posto i dati già inseriti: meglio decidere adesso.

**2. L'elenco delle tipologie di evento.** Chiedi il campo ma non elenchi le voci, e senza la lista
non posso disegnare il menu a tendina. Mi serve da te.

**3. Le attività professionali devono ancora indicare una data?** Oggi il sistema **pretende** una
data di inizio, altrimenti la scheda non si pubblica. Un dog sitter o un toelettatore non hanno una
data: così com'è, non possono andare online. È un blocco vero.
→ *Cosa farei: data facoltativa per le attività, obbligatoria per gli eventi.* Dimmi però cosa vuoi
che compaia sulla scheda al posto di "Data inizio / Data fine": gli orari di apertura? la zona in cui
opera? niente?

**4. "Evento singolo o ricorrente": è un'etichetta o una ricorrenza vera?** Fa una differenza enorme.
Se è un'etichetta descrittiva sulla scheda, è mezza giornata. Se il sistema deve generare davvero le
date ripetute — ogni martedì, fino a dicembre — sono una settimana abbondante in più, perché oggi il
sistema tiene una sola data per scheda e va rifatto.
→ *Cosa farei: partiamo dall'etichetta, la ricorrenza vera la valutiamo dopo.*

**5. "Posti limitati" e "prenotazione obbligatoria o facoltativa": cambiano il comportamento del
sito o sono informazioni?** Se "posti limitati" è un **numero**, il sistema blocca davvero le
prenotazioni oltre soglia e l'evento può esaurirsi; se è un sì/no, resta scritto sulla scheda e
basta. Stessa domanda per la prenotazione obbligatoria. E per gli eventi il **luogo** resta
obbligatorio come oggi?
→ *Cosa farei: posti limitati come numero che blocca davvero; prenotazione obbligatoria/facoltativa
come sola informazione sulla scheda; luogo obbligatorio.*

**6. I contatti sulla scheda: quali, e di chi?** Qui devo essere chiaro su una cosa. **Telefono,
email pubblica, sito e orari oggi non esistono da nessuna parte nel sistema.** L'unico telefono e
l'unica email che abbiamo sono quelli che il partner usa per accedere e per la fatturazione:
pubblicarli di nostra iniziativa non mi sembra corretto, e preferisco che la decisione sia tua e
scritta.

Le strade sono due:
- **(a)** chiediamo al partner dei recapiti pubblici nuovi, con una spunta di consenso nel suo
  profilo. Più pulito, e lui sceglie cosa mostrare;
- **(b)** pubblichiamo quelli della registrazione.

→ *Cosa farei: la (a).* Dimmi anche quali voci vuoi: telefono, WhatsApp, email, sito, indirizzo,
orari di apertura. Ogni voce in più è un campo in più da chiedere al partner.

Nel frattempo la scheda mostra ragione sociale, indirizzo e il link "dove pagare o prenotare": cose
che il partner ha già dato sapendo che sarebbero state pubbliche.

Una conseguenza da mettere sul tavolo adesso: con i contatti in chiaro il cliente può prenotare
direttamente col partner, **fuori dalla piattaforma e fuori dalla commissione**. Per chi non incassa
online non cambia nulla, non c'era commissione comunque. Ma se domani lo stesso partner collega
Stripe, i contatti vanno nascosti o la commissione la perdiamo: dimmi se li vuoi visibili solo
finché il partner non incassa online.

**7. Il "checkout in struttura" va tolto o tenuto spento?** Esiste ancora una procedura che permette
di **prenotare** online e pagare poi in struttura. Dalle schede non ci porta più nessun pulsante; dalle
liste sì, ed è il residuo del punto 1.
Nella tua mail scrivi che il checkout deve comparire «quando è realmente previsto un pagamento **o
una prenotazione online**»: se la prenotazione senza pagamento ti serve in futuro, quella procedura
va tenuta; se non ti serve, la tolgo del tutto.
→ *Cosa farei: la tengo spenta.* Toglierla e poi rifarla costa il doppio.

**8. Le liste e i carrelli già pieni.** Due strade da decidere insieme. Sulle **liste** posso togliere il
pulsante anche lì: costa una lettura in più per pagina, non per riquadro, quindi è un costo accettabile.
Sui **carrelli già pieni** posso svuotare quelle righe con un avviso, oppure bloccarle al momento del
pagamento spiegando di contattare la struttura.
→ *Cosa farei: togliere il pulsante dalle liste, e bloccare le righe vecchie al pagamento con il
messaggio e i contatti, senza svuotare il carrello a sua insaputa.* Dipende però dalla 7: se il
"prenota e paga in struttura" resta, quelle righe sono legittime e non si toccano.

**9. Le smartbox di un partner senza pagamento online.** Una smartbox è un cofanetto prepagato: se
il partner non incassa online, non si può comprare. Oggi la sua scheda mostra i contatti al posto del
pulsante, ma è una toppa — il prodotto resta in vetrina senza poter essere acquistato.
→ *Cosa farei: impedirne la pubblicazione finché il partner non collega il pagamento, spiegandogli il
perché nella sua area.* Dimmi se preferisci tenerla in vetrina come scheda informativa.

**10. La card "Servizi" nel menu di creazione.** Ora porta allo stesso percorso di "Attività", quindi
sono due porte per la stessa stanza. La tengo per non disorientare chi la conosce, o la fondo in
"Attività ed Eventi" come sembra suggerire il tuo punto 4?
→ *Cosa farei: la tengo, con la descrizione aggiornata.*

## Due cose che non mi hai chiesto ma devi sapere

**Sette servizi del catalogo non sono selezionabili da nessuna parte.** Lavanderia, Ascensore,
Noleggio bici, Dog sitter, Dog Beach nelle vicinanze, Supplemento animali, Piscina per cani: nessun
passaggio del percorso partner permette di indicarli. Finora comparivano sempre con la X rossa; da
adesso, giustamente, non compaiono mai. O li aggiungiamo al percorso, o li togliamo dal catalogo.
Dimmi tu.

**Agli eventi gratuiti non si iscrive nessuno davvero.** Il pulsante "Partecipa" apre un messaggio di
conferma e finisce lì: la partecipazione non viene registrata e non compare in "Eventi a cui
partecipo". Non è una conseguenza di queste modifiche, è così da prima — ma adesso che la scheda ha
perso il pulsante di acquisto si nota molto di più. Se vuoi che le iscrizioni vengano registrate
davvero, è un lavoro a sé.

## Cosa mi serve dal server

Per confermare la diagnosi su Stripe e recuperare le schede ferme mi serve un accesso, o che
qualcuno mi esegua quattro comandi. In particolare devo verificare quattro cose che dal codice non si
vedono:

1. se il processo automatico programmato (`schedule:run`) è davvero attivo in produzione — se non lo
   è, insieme alla pubblicazione delle schede si ferma anche l'invio dei bonifici ai partner;
2. se il processo che smaltisce le operazioni in coda è attivo: da lì passano le email e parte del
   lavoro di pubblicazione;
3. se è acceso il filtro di approvazione preventiva delle schede: in quel caso una scheda può essere
   pubblicata e comunque invisibile, e il partner non ha modo di saperlo;
4. quanti segreti di collegamento Stripe sono configurati: ne servono due, e se ce n'è uno solo
   Stripe smette progressivamente di comunicare con noi.

Fammi sapere e procedo.
