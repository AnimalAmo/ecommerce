<?php

/*
 * Meta description per pagina, indicizzate sul NOME DELLA ROTTA.
 *
 * Perché qui e non nei componenti: le pagine sono decine di componenti Livewire
 * e la description è copy, non logica — tenerla nel layout significa un solo
 * punto da cambiare quando il cliente riscrive un testo, e nessun rischio di
 * dimenticarne una. Il layout legge request()->route()->getName() e ripiega su
 * 'default' quando quella rotta non ha una voce sua.
 *
 * Le chiavi sono piatte e contengono il punto ('holiday.region'): sono i nomi
 * di rotta copiati uno a uno, non una gerarchia. Arr::get() — e quindi __() —
 * controlla per prima cosa la chiave esatta, perciò 'seo.holiday.region'
 * risolve senza bisogno di annidare gli array.
 *
 * Lunghezza: 150-160 caratteri, verificata da MetaTagsTest. Sotto i 150 Google
 * completa lo snippet con testo pescato dalla pagina, sopra i 160 lo tronca.
 */

return [
    // Ripiego di sito: vale per carrello, checkout, profilo, dettagli e per
    // qualunque rotta nuova che nasca senza copy propria.
    'default' => 'AnimalAmo è il portale del viaggio pet friendly: strutture che accolgono cani e gatti, eventi, attività e cofanetti regalo da vivere insieme in tutta Italia.',

    'home' => 'Cerca e prenota vacanze pet friendly in Italia: hotel e case vacanza che accolgono cani e gatti, eventi, attività e smartbox pensati per te e il tuo animale.',

    'holiday' => 'Animal Holiday: hotel, B&B, case vacanza e agriturismi pet friendly regione per regione, con i servizi dedicati al tuo animale e la prenotazione online.',
    'holiday.region' => 'Tutte le strutture pet friendly della regione: hotel, case vacanza e agriturismi che accolgono cani e gatti, con i servizi dedicati e la prenotazione online.',

    'eventi' => 'Eventi e attività pet friendly in Italia: escursioni, puppy yoga, raduni e giornate all\'aria aperta da vivere insieme al tuo animale. Prenota il tuo posto.',

    'smartbox' => 'Smartbox di AnimalAmo: cofanetti regalo per weekend e soggiorni pet friendly, con esperienze e servizi dedicati agli animali. Il regalo per chi ama viaggiare.',

    'news' => 'Animal Times: guide, consigli e novità per viaggiare con cani e gatti, dalle regole di trasporto al benessere del tuo animale prima, durante e dopo la vacanza.',
    'community' => 'Animal Network: la community di AnimalAmo dove condividere foto, esperienze e consigli di viaggio con gli altri proprietari di cani, gatti e non solo.',

    'about' => 'Chi siamo: AnimalAmo nasce per rendere semplice viaggiare con gli animali e mette in contatto le famiglie pet con strutture, eventi e servizi selezionati.',
    'contact' => 'Scrivi ad AnimalAmo per informazioni su prenotazioni, smartbox ed eventi, o per proporre la tua struttura pet friendly. Ti rispondiamo nel giro di poche ore.',
    'work-with-us' => 'Lavora con noi: porta la tua struttura, i tuoi eventi o i tuoi servizi pet friendly su AnimalAmo e raggiungi chi viaggia con cani e gatti in tutta Italia.',

    'terms.customers' => 'Termini e condizioni del servizio AnimalAmo per i clienti: prenotazioni, pagamenti, cancellazioni, rimborsi e diritti di chi acquista soggiorni ed esperienze.',
    'terms.suppliers' => 'Condizioni generali di adesione dei fornitori AnimalAmo: come pubblicare strutture, eventi e smartbox, commissioni, pagamenti e obblighi di ogni partner.',
    'privacy' => 'Privacy policy di AnimalAmo: quali dati raccogliamo, come li trattiamo, per quanto li conserviamo e quali diritti hai sui tuoi dati quando usi il sito.',
];
