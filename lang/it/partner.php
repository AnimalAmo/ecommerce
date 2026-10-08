<?php

return [

    // Errori del flusso partner (toast danger / eccezioni di dominio).
    'errors' => [
        'stripe_onboarding_required' => 'Per pubblicare un servizio devi prima completare il collegamento del conto su Stripe.',
        // Bozza chiusa senza i dati minimi per il catalogo (nome, stanze, data o prezzo).
        'draft_not_publishable' => 'Non possiamo ancora pubblicare questo servizio: mancano alcuni dati obbligatori. Ricontrolla gli step e riprova.',
        // Utente partner senza riga `partner_profiles`: prima creava l'account su
        // Stripe e poi esplodeva, lasciando un account orfano a ogni click.
        'partner_profile_missing' => 'Non possiamo aprire il collegamento con Stripe: il tuo profilo partner non è completo. Scrivici e lo sistemiamo.',
        // Smartbox di un partner che non incassa online (richiesta della cliente, 27/09/2026).
        'smartbox_requires_online_payment' => 'Una Smartbox è un cofanetto prepagato: per pubblicarla e venderla è necessario incassare online e collegare il sistema di pagamento.',
    ],

    // Pubblicazione automatica al collegamento Stripe (P4): avviso in dashboard dopo il wizard.
    'publish' => [
        'awaiting_stripe' => 'Il tuo servizio è pronto: lo pubblicheremo in automatico appena completi il collegamento del conto su Stripe.',
        // Modifica di un servizio già completato: la versione precedente resta quella pubblicata.
        'awaiting_stripe_changes' => "Modifiche salvate: la versione già pubblicata resta com'era e pubblicheremo le modifiche in automatico appena completi il collegamento del conto su Stripe.",
        // Smartbox chiusa da chi incassa in struttura (difetto F2, 28/09/2026):
        // prima leggeva gli avvisi qui sopra, collegava Stripe e la smartbox
        // restava ferma, perché le serve l'incasso online.
        'awaiting_payment_method' => 'La tua Smartbox è pronta: la metteremo in vetrina in automatico appena scegli di ricevere i pagamenti online su AnimalAmo, con il conto Stripe collegato.',
        'awaiting_payment_method_changes' => 'Modifiche salvate: le pubblicheremo in automatico appena scegli di ricevere i pagamenti online su AnimalAmo, con il conto Stripe collegato.',
        // Avviso del wizard smartbox e della card Smartbox: avvisa, non blocca
        // (richiesta della cliente del 27/09/2026). La prima riga è la frase
        // della cliente, la seconda dice che si può compilare comunque.
        'smartbox_payment_required' => 'Per pubblicare e vendere una Smartbox è necessario collegare il sistema di pagamento',
        'smartbox_payment_required_hint' => 'Puoi compilare e salvare il cofanetto da subito: andrà in vetrina da solo quando ricevi i pagamenti online su AnimalAmo con il conto Stripe collegato.',
        'smartbox_payment_required_cta' => 'Collega il sistema di pagamento',
    ],

    // "I miei servizi": bozze chiuse dal partner e ferme finché non è pagabile su Stripe.
    // Perché un servizio non è (ancora) online. Prima c'era la sola dicitura
    // "in attesa di Stripe", mostrata su ogni bozza ferma: chi era fermo per
    // un altro motivo leggeva una diagnosi falsa (segnalazione del 29/09/2026).
    'my_services' => [
        'awaiting_stripe' => 'In attesa del collegamento Stripe',
        // Smartbox ritirata dalla vetrina, o ferma prima di entrarci, perché il
        // partner non incassa online (27/09/2026): non è una sospensione.
        'awaiting_payment_method' => 'Serve il sistema di pagamento',
        'awaiting_payment_method_hint' => 'Una Smartbox si vende solo con i pagamenti online: collega il sistema di pagamento e torna in vetrina da sola.',
        'publishing' => 'In pubblicazione',
        'publishing_hint' => 'Va online entro pochi minuti.',
        'incomplete' => 'Mancano dei dati',
        'incomplete_hint' => 'Aprila e completa i campi obbligatori: com’è adesso non può andare online.',
        'awaiting_approval' => 'In attesa di approvazione',
        'suspended' => 'Sospesa',
        // Bozza mai finita. Prima non compariva in elenco, e chi usciva dal
        // wizard non aveva più nessun modo di ritrovarla: il lavoro sembrava
        // cancellato (segnalazione di un partner, 27/09/2026).
        'draft' => 'Bozza in corso',
        'draft_hint' => 'Non è ancora online: riprendila da dove l’hai lasciata.',
        'resume' => 'Riprendi',
    ],

    // Modalità di pagamento del partner: online su AnimalAmo o direttamente al partner.
    'payment_mode' => [
        'section' => 'Come ricevi i pagamenti',
        'help' => 'Scegli se i clienti pagano online su AnimalAmo o direttamente a te, in struttura o sul tuo sito. Le prenotazioni già fatte restano come sono.',
        'online' => 'Online su AnimalAmo',
        'on_site' => 'Direttamente a me, in struttura o sul mio sito',
        'url_label' => 'Sito dove pagare o prenotare (facoltativo)',
        // Dove lo vedono davvero i clienti: la card contatti delle schede a
        // pagamento, solo quando il partner si fa pagare direttamente
        // (PartnerContacts). Il testo di prima citava la conferma della
        // prenotazione, che per chi si fa pagare in struttura oggi non nasce:
        // la scheda non ha il carrello e commerce.on_site_booking è spento.
        // Sulle attività e sugli eventi gratuiti («Partecipa») la card non c'è.
        'url_help' => 'Se ti fai pagare direttamente, i clienti lo vedono sulle tue schede a pagamento, nel riquadro dei contatti.',
        'save' => 'Salva la modalità',
        'saved' => 'Modalità di pagamento aggiornata',
        'online_needs_stripe' => 'Per scegliere il pagamento online collega prima il tuo conto Stripe dal riquadro qui sotto.',
        'errors' => [
            'stripe_required' => 'Per ricevere i pagamenti online devi prima completare il collegamento del conto su Stripe.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner — Lavora con noi
    |--------------------------------------------------------------------------
    |
    | Stringhe UI per la candidatura partner (form "Lavora con noi") e la
    | pagina di ringraziamento.
    |
    */

    // Titoli tab (componenti Livewire)
    'title_work_with_us' => 'AnimalAmo — Lavora con noi',
    'throttle' => 'Hai già inviato diverse candidature. Riprova tra :seconds secondi.',
    'title_thanks' => 'AnimalAmo — Grazie',

    // Intestazione
    'heading' => 'Iscrivi la tua struttura o un’attività pet friendly',
    'intro' => '🐾 L’iscrizione è gratuita! Cerchiamo strutture, attività ed eventi che condividano la nostra filosofia pet friendly. Per le strutture ricettive è richiesto che i cani siano i benvenuti sia nelle camere sia nelle aree comuni, così da garantire un’esperienza davvero pet friendly.',

    // Campi form
    'first_name' => 'Nome',
    'last_name' => 'Cognome',
    'email' => 'Email',
    'phone' => 'Cellulare',
    'website' => 'Sito web',
    'city' => 'Città',
    'business_name' => 'Nome attività',
    'role' => 'Il tuo ruolo',
    'offer_type' => 'Tipologia Offerta',
    'description' => 'Descrizione',
    'description_placeholder' => 'Descrivi il servizio che vorresti offrire, il luogo e alcune caratteristiche',
    'select_placeholder' => 'Seleziona tipologia',
    'email_account_hint' => 'È l’email del tuo account AnimalAmo: la richiesta resta collegata a questo profilo.',
    'password' => 'Password',
    'password_confirmation' => 'Ripeti la password',
    'password_hint' => 'Con questa password entri subito nella tua area partner. Partita IVA, codice fiscale e indirizzo li aggiungi dopo, dal tuo profilo.',

    // Ruoli
    'role_owner' => 'Proprietario',
    'role_manager' => 'Gestore',
    'role_employee' => 'Dipendente',
    'role_other' => 'Altro',

    // Tipologie offerta
    'offer_accommodation' => 'Hotel e strutture ricettive',
    'offer_activities' => 'Attività ed Eventi',
    'offer_dining' => 'Ristorazione',
    'offer_pet_services' => 'Servizi per animali',
    'offer_other' => 'Altro',

    // CTA
    'submit' => 'Iscriviti',

    // Pagina ringraziamento
    'thanks_heading' => 'Grazie!',
    'thanks_line_1' => 'La tua richiesta è stata inoltrata correttamente.',
    'thanks_line_2' => 'Ti risponderemo il prima possibile.',
    'back_home' => 'Torna alla Home',
    'thanks_continue' => 'Completa l’iscrizione',

    /*
    |--------------------------------------------------------------------------
    | Chrome B2B (header + footer dell'area partner)
    |--------------------------------------------------------------------------
    */
    'locale_it' => 'IT',
    'locale_en' => 'EN',
    'nav_help' => 'Aiuto',
    'nav_dashboard' => 'Dashboard',
    'nav_create_service' => 'Crea servizio',
    'nav_my_services' => 'I miei servizi',
    'nav_bookings' => 'Prenotazioni',
    'nav_profile' => 'Profilo',
    'help_contact' => 'Contattaci',
    'help_faq' => 'Faq',

    'footer_company' => 'Azienda',
    'footer_about' => 'Informazioni su Animal_Amo',
    'footer_website' => 'Sito web',
    'footer_help' => 'Help & Support',
    'footer_security' => 'Sicurezza',
    'footer_privacy' => 'Privacy Policy',
    'footer_cookie' => 'Cookie Policy',
    'footer_terms' => 'Termini e Condizioni',

    /*
    |--------------------------------------------------------------------------
    | Profilo partner (info personali / metodo di pagamento / sicurezza)
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'stripe' => [
            'connected' => 'Conto collegato',
            'connected_help' => 'I pagamenti dei tuoi clienti arrivano direttamente sul tuo conto Stripe.',
            'disconnected' => 'Collega il tuo conto per ricevere i pagamenti',
            'incomplete' => 'Collegamento da completare',
            'help' => 'I clienti pagano direttamente te: il denaro arriva sul tuo conto Stripe, e AnimalAmo trattiene solo la propria provvigione. Finché il collegamento non è completo non puoi pubblicare i tuoi servizi.',
            'help_on_site' => 'Oggi i tuoi clienti ti pagano direttamente. Se vuoi passare al pagamento online, collega qui il tuo conto Stripe: puoi farlo quando vuoi.',
            'connect' => 'Collega il conto',
            'resume' => 'Riprendi il collegamento',
        ],
        'title' => 'Profilo',
        'nav_profile' => 'Profilo',
        'nav_payment' => 'Metodo di pagamento',
        'nav_security' => 'Sicurezza',
        'save' => 'Salva',
        'saved' => 'Modifiche salvate',

        'info_title' => 'AnimalAmo — Informazioni personali',
        'info_heading' => 'Informazioni personali',
        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'business_name' => 'Ragione Sociale',
        // Un campo solo, sul partner (27/09/2026): la cliente li nomina sia fra
        // i campi dell'attività sia fra i recapiti pubblici, e due campi che
        // possono contraddirsi sono peggio di uno.
        'opening_hours' => 'Orari di apertura o disponibilità',
        'opening_hours_hint' => 'Facoltativi. Compaiono sulle tue schede, per esempio «Lun-Ven 9-18».',
        // Recapiti pubblici (risposta della cliente, 26/09/2026, punto 6): voci
        // nuove, mai quelle di registrazione. Telefono, WhatsApp, email e sito
        // scavalcherebbero la piattaforma, quindi con l'incasso online restano
        // nascosti; l'intro lo dice prima che il partner li scriva.
        'public_contacts_heading' => 'Recapiti pubblici',
        'public_contacts_intro' => 'Facoltativi: sono i contatti che vuoi mostrare ai clienti, non quelli con cui ti sei registrato. Compaiono sulle tue schede solo se dai il consenso qui sotto. Se incassi online su AnimalAmo, telefono, WhatsApp, email e sito restano nascosti: si vedono l’indirizzo e gli orari.',
        'public_phone' => 'Telefono',
        'public_whatsapp' => 'WhatsApp',
        'public_email' => 'Email di contatto',
        'public_website' => 'Sito web',
        'public_address' => 'Indirizzo per i clienti',
        'public_contacts_consent' => 'Acconsento a pubblicare questi recapiti sulle mie schede AnimalAmo.',
        'email' => 'Email',
        'address' => 'Indirizzo',
        'province' => 'Provincia',
        'city' => 'Città',
        'zip' => 'Cap',
        'vat' => 'Partita IVA',
        'phone' => 'Cellulare',
        'tax_code' => 'Codice Fiscale',

        'payment_title' => 'AnimalAmo — Metodo di pagamento',
        'payment_heading' => 'Metodo di pagamento',
        'account_holder' => 'Titolare Conto',
        'iban' => 'IBAN',
        'bic' => 'BIC',

        'security_title' => 'AnimalAmo — Sicurezza',
        'security_heading' => 'Sicurezza',
        'password' => 'Password',
        'reset_password' => 'Reimposta password',
        'privacy_settings' => 'Impostazioni sulla Privacy',
        'delete_account' => 'Elimina Account',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prenotazioni (area riservata)
    |--------------------------------------------------------------------------
    */
    'bookings' => [
        'title' => 'AnimalAmo — Prenotazioni',
        'heading' => 'Prenotazioni',
        'tab_strutture' => 'Strutture',
        'tab_eventi' => 'Eventi',
        'tab_attivita' => 'Attività',
        'tab_smartbox' => 'Smartbox',
        'search_placeholder' => 'Cerca',
        'date_placeholder' => 'Seleziona data',
        'col_id' => 'ID prenotazione',
        'col_first_name' => 'Nome',
        'col_last_name' => 'Cognome',
        'col_email' => 'Email',
        'col_structure' => 'Struttura',
        'col_event' => 'Evento',
        'col_activity' => 'Attività',
        'col_smartbox' => 'Nome Smartbox',
        'col_date' => 'Data',
        'col_time' => 'Ora',
        'col_validity' => 'Validità',
        'col_price' => 'Prezzo',
        'col_people' => 'N. Persone',
        'view' => 'Vedi prenotazione',
        'print' => 'Stampa prenotazione',
        'actions' => 'Azioni',
        'empty' => 'Nessuna prenotazione trovata.',
        'detail_title' => 'AnimalAmo — Dettaglio prenotazione',
        'detail_back' => 'Indietro',
        'detail_print' => 'Stampa',
        'detail_customer' => 'Info cliente',
        'detail_booking' => 'Info prenotazione',
        'detail_first_name' => 'Nome:',
        'detail_last_name' => 'Cognome:',
        'detail_email' => 'Email:',
        'detail_phone' => 'Cellulare:',
        'detail_id' => 'ID di prenotazione:',
        'detail_payment_method' => 'Metodo di pagamento:',
        'detail_booking_date' => 'Data prenotazione:',
        'detail_time' => 'Orario:',
        'detail_language' => 'Lingua:',
        'detail_structure' => 'Struttura:',
        'detail_room' => 'Stanza:',
        'detail_validity' => 'Validità:',
        'detail_price' => 'Prezzo:',
        'detail_people' => 'N. Persone:',
        'detail_duration' => 'Durata:',
        'duration_nights' => '{1} 1 notte|[2,*] :count notti',
        'duration_days' => '{1} 1 giorno|[2,*] :count giorni',
        'duration_hours' => '{1} 1 ora|[2,*] :count ore',
        // Come è pagata la prenotazione (copia salvata sull'ordine).
        'col_payment' => 'Pagamento',
        'detail_payment' => 'Pagamento:',
        'paid_online' => 'Pagato online',
        'pay_on_site' => 'Pagamento diretto',
        'to_collect' => 'Da incassare in struttura: :amount',
    ],

    /*
    |--------------------------------------------------------------------------
    | I miei servizi (lista + dettaglio + popup eliminazione)
    |--------------------------------------------------------------------------
    */
    'services' => [
        'title' => 'AnimalAmo — I miei servizi',
        'heading' => 'I miei servizi',
        'tag_struttura' => 'Holiday',
        'tag_attivita' => 'Eventi',
        'tag_smartbox' => 'Smartbox',
        'view_details' => 'Vedi dettagli',
        'delete' => 'Elimina servizio',
        'edit' => 'Modifica servizio',
        'delete_confirm' => 'Sei sicuro di voler eliminare:',
        'delete_cancel' => 'Annulla',
        'delete_submit' => 'Elimina',
        'empty' => 'Non hai ancora creato servizi.',

        'detail_title' => 'AnimalAmo — Dettaglio servizio',
        'back' => 'Indietro',
        'not_provided' => 'Non specificato',
        'section_type' => 'Tipologia struttura',
        'section_name' => 'Nome',
        'section_location' => 'Luogo',
        'section_description' => 'Descrizione',
        'section_rooms' => 'Informazioni sulle stanze',
        'section_cancellation' => 'Cancellazione',
        'section_services' => 'Servizi',
        'section_extra' => 'Informazioni aggiuntive',
        'section_photos' => 'Foto',
        'section_payment' => 'Metodo di pagamento',
        // Righe del dettaglio per le famiglie diverse dalla struttura e per i
        // campi che il dettaglio non mostrava (audit del 28/09/2026, F7 e W6):
        // «Tipologia struttura» su un evento o una smartbox non è vero.
        'section_service_type' => 'Tipologia',
        'section_detailed_description' => 'Descrizione dettagliata',
        'section_cost' => 'Costo',
        'section_smartbox' => 'Adesione alle smartbox',
        'max_participants_unlimited' => 'Nessun limite',
        'rooms_count' => 'stanze',
        'rooms_price' => 'prezzo a notte',
        'checkin' => 'Check-in',
        'checkout' => 'Check-out',
        'cancellation_days' => ':days giorni prima',
        'cancellation_day' => '1 giorno prima',
    ],

    /*
    |--------------------------------------------------------------------------
    | Iscrizione B2B — step 1 (Informazioni personali)
    |--------------------------------------------------------------------------
    */
    'register' => [
        'title' => 'AnimalAmo — Iscrizione partner',
        'heading' => 'Unisciti a noi come partner',
        'step' => 'Step 1 di 2',
        'section_personal' => 'Informazioni personali',
        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'business_name' => 'Ragione Sociale',
        'email' => 'Email',
        'address' => 'Indirizzo',
        'province' => 'Provincia',
        'zip' => 'Cap',
        'phone' => 'Cellulare',
        'vat' => 'Partita IVA',
        'tax_code' => 'Codice Fiscale',
        'back' => 'Indietro',
        'next' => 'Prosegui',
        'error_email_taken' => 'Esiste già un account con questa email: accedi per continuare l’iscrizione con quell’account.',
        'invitation_other_account' => 'Questo invito è per :email. Sei collegato con un altro account, quindi l\'iscrizione creerebbe il partner sull\'indirizzo sbagliato: esci e riapri il link dalla casella invitata.',
        'invitation_logout' => 'Esci da questo account',
        'account_ready' => 'Il tuo account partner è già pronto: lo abbiamo creato noi per :email. Accedi con questa email; se non hai ancora la password, usa “Password dimenticata”.',
        'account_ready_login' => 'Accedi',
        'error_account_inactive' => 'Il tuo account è disattivato: scrivi all’assistenza per riattivarlo e completare l’iscrizione.',
        'login_and_continue' => 'Accedi e continua',
        'contact_support' => 'Contatta l’assistenza',
    ],

    /*
    |--------------------------------------------------------------------------
    | Iscrizione B2B — step 2 (scelta tipologia servizio)
    |--------------------------------------------------------------------------
    */
    'register2' => [
        'step' => 'Step 2 di 2',
        'section' => 'Seleziona il servizio che vorrai proporre',
        'struttura_title' => 'Struttura ricettiva',
        'struttura_subtitle' => 'Come Hotel, Agriturismo, B&B, altro',
        'attivita_title' => 'Attività',
        'attivita_subtitle' => 'Come una gita di un giorno, un ritrovo con i propri animali',
        // Il testo cambia (richiesta della cliente, 27/09/2026), la chiave no:
        // due test leggono 'servizi_title', e il valore del radio resta 'servizi'.
        'servizi_title' => 'Servizio professionale',
        'servizi_subtitle' => 'Come Toelettatore, Dog sitter, Educatore cinofilo, Maneggio, altro',
        'eventi_title' => 'Evento',
        'eventi_subtitle' => 'Come una passeggiata, un corso, una fiera pet-friendly',
        'submit' => 'Crea un account',
        'error_required' => 'Seleziona almeno un servizio.',
        'payment_mode' => [
            'label' => 'Come vuoi essere pagato?',
            'online_title' => 'Online su AnimalAmo',
            'online_subtitle' => 'Il cliente paga con carta al momento della prenotazione. Per pubblicare dovrai collegare il tuo conto Stripe.',
            'on_site_title' => 'Direttamente a me, in struttura o sul mio sito',
            'on_site_subtitle' => 'Il cliente prenota su AnimalAmo e paga te, senza pagamento online. Puoi cambiare idea dal tuo profilo.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attività/eventi — steps 6-10 (le liste riusano le stringhe di hotel_*)
    |--------------------------------------------------------------------------
    */
    'activity_included' => [
        'title' => 'AnimalAmo — Cosa è incluso',
        'step' => 'Step 6 di 10',
        'heading' => 'Cosa è incluso?',
        'section' => 'Aggiungi le informazioni per descrivere i servizi presenti (puoi selezionare più di un’opzione)',
        'helper' => 'Aiuteranno l’utente a valutare la struttura.',
    ],
    'activity_animal_services' => [
        'title' => 'AnimalAmo — Servizi animali',
        'step' => 'Step 7 di 10',
        'heading' => 'Cos’è incluso per gli animali?',
        'section' => 'Aggiungi le informazioni per descrivere i servizi che offri (puoi selezionare più di un’opzione)',
        'helper' => 'Aiuteranno l’utente a valutare la struttura.',
    ],
    'activity_cost' => [
        'title' => 'AnimalAmo — Costo',
        'step' => 'Step 8 di 10',
        'heading' => 'Costo dell’attività',
        'section' => 'Seleziona un’opzione',
        'opt_paid' => 'A pagamento',
        'opt_free' => 'Gratuito',
        'price_label' => 'Costo a persona',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Seleziona un’opzione di costo.',
    ],
    'activity_photos' => [
        'title' => 'AnimalAmo — Foto',
        'step' => 'Step 9 di 10',
    ],
    'activity_cancellation' => [
        'title' => 'AnimalAmo — Cancellazione',
        'step' => 'Step 10 di 10',
        'save' => 'Salva',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attività/eventi — informazioni generali (step 5 di 10)
    |--------------------------------------------------------------------------
    */
    'activity_info' => [
        'title' => 'AnimalAmo — Informazioni generali',
        'step' => 'Step 5 di 10',
        'heading' => 'Informazioni generali',
        'section_activity' => 'Aggiungi le informazioni sulla tua attività',
        'section_event' => 'Aggiungi le informazioni sul tuo evento',
        'date_start' => 'Data inizio',
        'date_end' => 'Data fine',
        'time_start' => 'Ora inizio',
        'time_end' => 'Ora fine',
        // Campi nati dalle risposte della cliente del 27/09/2026. La data è
        // facoltativa per un'attività e obbligatoria per un evento, quindi
        // l'etichetta lo dice invece di lasciarlo scoprire dall'errore.
        'date_optional_hint' => 'Facoltativa: un servizio professionale può non avere una data.',
        'recurrence' => 'L’evento è singolo o ricorrente?',
        'booking_requirement' => 'Prenotazione',
        'booking_requirement_hint' => 'Compare sulla scheda: il cliente sa subito se deve prenotare.',
        'max_participants' => 'Posti disponibili',
        'max_participants_hint' => 'Lascia vuoto se non c’è un limite. Al raggiungimento del limite le iscrizioni si chiudono.',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attività/eventi — descrizione (step 4 di 10)
    |--------------------------------------------------------------------------
    */
    'activity_description' => [
        'title' => 'AnimalAmo — Descrizione',
        'step' => 'Step 4 di 10',
        'heading' => 'Descrizione',
        'section' => 'Presenta la tua attività',
        'helper' => 'Dai al cliente un assaggio di ciò che farà in 2 o 3 frasi. Questa sarà la prima cosa che i clienti leggeranno dopo il titolo e li ispirerà a continuare.',
        'detailed_label' => 'Descrivi in modo dettagliato la tua attività',
        'placeholder' => 'Descrizione',
        'chars' => 'caratteri',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Inserisci una descrizione.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attività/eventi — luogo (step 3 di 10)
    |--------------------------------------------------------------------------
    */
    'activity_location' => [
        'title' => 'AnimalAmo — Luogo',
        'step' => 'Step 3 di 10',
        'heading' => 'Luogo',
        'section' => 'Aggiungi le informazioni che descrivono la tua attività',
        'helper' => 'Aiuteranno l’utente a capire meglio che servizio è.',
        'address' => 'Indirizzo',
        'city' => 'Città',
        'province' => 'Provincia',
        'zip' => 'Cap',
        'meeting_point' => 'Punto d’incontro',
        // Al posto del punto d'incontro per chi non fa eventi (27/09/2026): un
        // professionista non ha un ritrovo, ha un raggio in cui lavora.
        'operating_area' => 'Zona in cui operi',
        'operating_area_hint' => 'Per esempio «Milano e provincia» oppure «Lombardia».',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attività/eventi — nome (step 2 di 10)
    |--------------------------------------------------------------------------
    */
    'activity_name' => [
        'title' => 'AnimalAmo — Nome attività',
        'step' => 'Step 2 di 10',
        'heading' => 'Il nome della tua attività',
        'section' => 'Qual’è il nome della tua attività?',
        'helper' => 'Aiuterà gli utenti a trovare la tua struttura velocemente',
        'field_label' => 'Nome attività',
        // Categorie professionali a scelta multipla (risposta della cliente,
        // 27/09/2026: «un maneggio può essere anche fattoria didattica»).
        'field_categories' => 'Tipologia di attività o servizio',
        // Gemella per il ramo eventi: la stessa etichetta su un evento diceva
        // «Tipologia di attività o servizio: Fiere / Mercatini», sia nel wizard
        // sia sulla scheda pubblica.
        'field_categories_event' => 'Tipologia di evento',
        'field_categories_hint' => 'Puoi selezionarne più di una.',
        'field_categories_other' => 'Descrivi la tipologia',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Inserisci il nome dell’attività.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — titolo (step 2 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_name' => [
        'title' => 'AnimalAmo — Titolo smartbox',
        'step' => 'Step 2 di 12',
        'heading' => 'Il titolo della smartbox',
        'section' => 'Qual’è il titolo della tua smartbox?',
        'helper' => 'Aiuterà gli utenti a trovare la tua struttura velocemente',
        'field_label' => 'Titolo smartbox',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Inserisci il titolo della smartbox.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — descrizione (step 3 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_description' => [
        'title' => 'AnimalAmo — Descrizione smartbox',
        'step' => 'Step 3 di 12',
        'heading' => 'Descrizione',
        'section' => 'Presenta la tua smartbox',
        'helper' => 'Dai al cliente un assaggio di ciò che farà in 2 o 3 frasi. Questa sarà la prima cosa che i clienti leggeranno dopo il titolo e li ispirerà a continuare.',
        'detailed_label' => 'Descrivi in modo dettagliato la smartbox',
        'placeholder' => 'Descrizione',
        'chars' => 'caratteri',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Inserisci una descrizione.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — durata (step 4 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_duration' => [
        'title' => 'AnimalAmo — Durata smartbox',
        'step' => 'Step 4 di 12',
        'heading' => 'Durata',
        'section' => 'Aggiungi le informazioni sulla durata della smartbox',
        'helper' => 'Quanti giorni dura la smartbox?',
        'field_label' => 'N. Giorni',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Indica quanti giorni dura la smartbox.',
        'error_min' => 'La durata deve essere di almeno 1 giorno.',
        'error_max' => 'La durata non può superare i 365 giorni.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — cancellazione (step 5 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_cancellation' => [
        'title' => 'AnimalAmo — Cancellazione smartbox',
        'step' => 'Step 5 di 12',
        'heading' => 'Cancellazione',
        'section' => 'Quando può l’ospite cancellare la prenotazione gratis?',
        'helper' => 'Aiuteranno l’utente a scegliere la struttura.',
        'when' => 'Quando?',
        'free' => 'Offri la cancellazione gratuita',
        'pays' => 'L’ospite paga l’importo totale',
        'arrival' => 'Data di arrivo',
        'days_30' => '30 giorni',
        'days_15' => '15 giorni',
        'days_7' => '7 giorni',
        'days_1' => '1 giorno',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Scegli quando la cancellazione è gratuita.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — aggiungi strutture (step 10 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_structures' => [
        'title' => 'AnimalAmo — Aggiungi strutture',
        'step' => 'Step 10 di 12',
        'heading' => 'Aggiungi strutture',
        'section' => 'Aggiungi le strutture che saranno visibili nella smartbox e tra cui gli utenti potranno scegliere',
        'empty_title' => 'Non hai ancora strutture pubblicate',
        'empty_text' => 'Le strutture che pubblichi compaiono qui e puoi includerle nel cofanetto. Puoi proseguire e aggiungerle più avanti, modificando il servizio.',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — foto (step 11 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_photos' => [
        'title' => 'AnimalAmo — Foto smartbox',
        'step' => 'Step 11 di 12',
        'heading' => 'Foto',
        'section' => 'Carica alcune foto',
        'helper' => 'Serviranno ad aumentare potenzialmente il tuo tasso di conversione in media del 2,7%, in altre parole, per aumentare le tue prenotazioni e i tuoi guadagni.',
        'drop' => 'Trascina qui le tue foto',
        'hint' => 'Devi caricare almeno 4 foto (7 o più consigliate)',
        'uploading' => 'Caricamento in corso…',
        'delete' => 'Elimina',
        'error_min' => 'Devi caricare almeno 4 foto.',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — prezzo / costo (step 12 di 12, finale)
    |--------------------------------------------------------------------------
    */
    'smartbox_price' => [
        'title' => 'AnimalAmo — Costo della smartbox',
        'step' => 'Step 12 di 12',
        'heading' => 'Costo della smartbox',
        'section' => 'Inserire il costo totale della smartbox',
        'field_label' => 'Prezzo',
        'back' => 'Indietro',
        'save' => 'Salva',
        'error_required' => 'Inserisci il costo della smartbox.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — cosa è incluso (step 8 di 12) — opzioni riusano hotel_services.svc_*
    |--------------------------------------------------------------------------
    */
    'smartbox_included' => [
        'title' => 'AnimalAmo — Cosa è incluso',
        'step' => 'Step 8 di 12',
        'heading' => 'Cosa è incluso?',
        'section' => 'Aggiungi le informazioni per descrivere i servizi presenti (puoi selezionare più di un’opzione)',
        'helper' => 'Aiuteranno l’utente a valutare la struttura.',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — cosa è incluso per gli animali (step 9 di 12) — opzioni riusano hotel_animal_services.*
    |--------------------------------------------------------------------------
    */
    'smartbox_included_animals' => [
        'title' => 'AnimalAmo — Cosa è incluso per gli animali',
        'step' => 'Step 9 di 12',
        'heading' => 'Cos’è incluso per gli animali?',
        'section' => 'Aggiungi le informazioni per descrivere i servizi che offri (puoi selezionare più di un’opzione)',
        'helper' => 'Aiuteranno l’utente a valutare la struttura.',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — alloggio / cosa troverai (step 7 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_offers' => [
        'title' => 'AnimalAmo — Cosa offre la smartbox',
        'step' => 'Step 7 di 12',
        'heading' => 'Cosa offre la smartbox',
        'section' => 'Seleziona le opzioni che saranno presenti nella smartbox',
        'helper' => 'Aiuteranno l’utente a valutare la tua proposta.',
        'additional_heading' => 'Servizi aggiuntivi presenti',
        'amenity_bedroom' => 'Camera da letto',
        'amenity_bathroom' => 'Bagno',
        'amenity_kitchen' => 'Cucina',
        'amenity_balcony' => 'Balcone',
        'amenity_terrace' => 'Terrazzo',
        'add_pool' => 'Piscina',
        'add_spa' => 'Spa',
        'add_tennis' => 'Campo da tennis',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Smartbox — cibo / pasti (step 6 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_meals' => [
        'title' => 'AnimalAmo — Cibo smartbox',
        'step' => 'Step 6 di 12',
        'heading' => 'Cibo',
        'section' => 'Seleziona una o più opzioni',
        'meal_none' => 'Nessuno',
        'meal_breakfast' => 'Colazione',
        'meal_lunch' => 'Pranzo',
        'meal_dinner' => 'Cena',
        'times_heading' => 'Inserisci gli orari',
        'time_from' => 'Ora inizio',
        'time_to' => 'Ora fine',
        'dietary_heading' => 'Quali restrizioni dietetiche puoi soddisfare?',
        'diet_diabetic' => 'Diabetico',
        'diet_vegan' => 'Vegano',
        'diet_vegetarian' => 'Vegetariano',
        'diet_gluten_free' => 'Senza glutine',
        'diet_egg_free' => 'Senza uova',
        'diet_lactose_free' => 'Senza lattosio',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Crea una smartbox — tipologia (step 1 di 12)
    |--------------------------------------------------------------------------
    */
    'smartbox_type' => [
        'title' => 'AnimalAmo — Crea una smartbox',
        'step' => 'Step 1 di 12',
        'heading' => 'Crea una smartbox',
        'soggiorno' => 'Soggiorno',
        'benessere' => 'Benessere',
        'avventura' => 'Avventura',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Seleziona una tipologia.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipologia attività/eventi (step 1 di 10 del flusso attività ed eventi)
    |--------------------------------------------------------------------------
    */
    // Categorie professionali dell'attivita, scelta multipla: le otto voci
    // confermate dalla cliente il 26/09/2026. Tre accorpano sinonimi con la
    // barra, ed e come le ha scritte lei.
    'activity_category' => [
        'toelettatore' => 'Toelettatore',
        'asilo_cani' => 'Asilo per cani',
        'dog_sitter' => 'Dog sitter / Pet sitter',
        'educatore_cinofilo' => 'Educatore cinofilo / Addestratore',
        'fotografo_pet' => 'Fotografo pet',
        'maneggio' => 'Maneggio / Centro equestre',
        'fattoria_didattica' => 'Fattoria didattica',
        'altro' => 'Altro',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipologie di evento (risposta della cliente, 27/09/2026) — scelta multipla
    |--------------------------------------------------------------------------
    | Uno slug per voce anche dove la voce accorpa sinonimi con la barra: due
    | slug per la stessa voce sdoppierebbero ogni filtro futuro.
    */

    'event_category' => [
        'passeggiate_trekking' => 'Passeggiate / Trekking con animali',
        'educativi_esperti' => 'Eventi educativi / Incontri con esperti',
        'corsi_workshop' => 'Corsi / Workshop',
        'sportivi' => 'Eventi sportivi',
        'fattoria' => 'Giornate in fattoria / Attività con animali',
        'fiere_mercatini' => 'Fiere / Mercatini / Manifestazioni',
        'solidali_adozioni' => 'Eventi solidali / Adozioni',
        'speciali_pet_friendly' => 'Eventi e giornate speciali pet-friendly',
        'altro' => 'Altro',
    ],

    // Sola etichetta sulla scheda: la cliente ha escluso la generazione delle
    // date ripetute («non serve in questa fase creare automaticamente tutte le
    // ricorrenze»).
    'event_recurrence' => [
        'singolo' => 'Evento singolo',
        'ricorrente' => 'Evento ricorrente',
    ],

    // Vale per attività, servizi professionali ed eventi: la cliente chiede la
    // «possibilità di prenotazione» per i primi e «obbligatoria o facoltativa»
    // per i secondi, che è la stessa informazione con tre stati.
    'booking_requirement' => [
        'obbligatoria' => 'Prenotazione obbligatoria',
        'facoltativa' => 'Prenotazione facoltativa',
        'non_prevista' => 'Nessuna prenotazione',
    ],

    'activity_type' => [
        'title' => 'AnimalAmo — Attività ed Eventi',
        'step' => 'Step 1 di 10',
        'attivita' => 'Attività',
        'eventi' => 'Eventi',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Seleziona una tipologia.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Crea servizio (scelta tipologia)
    |--------------------------------------------------------------------------
    */
    'create_service' => [
        'title' => 'AnimalAmo — Crea servizio',
        'heading' => 'Crea un nuovo servizio',
        'section' => 'Seleziona il servizio che vuoi proporre',
        'helper' => 'Questo ci aiuta a classificare il tuo prodotto in modo che i clienti possano trovarlo.',
        'struttura_title' => 'Struttura ricettiva',
        'struttura_subtitle' => 'Come Hotel, Agriturismo, B&B, altro',
        'attivita_title' => 'Attività',
        'attivita_subtitle' => 'Come una gita di un giorno, un ritrovo con i propri animali',
        'servizi_title' => 'Servizio professionale',
        'servizi_subtitle' => 'Come Toelettatore, Dog sitter, Educatore cinofilo, Maneggio, altro',
        'eventi_title' => 'Evento',
        'eventi_subtitle' => 'Come una passeggiata, un corso, una fiera pet-friendly',
        'smartbox_title' => 'Smartbox',
        'smartbox_subtitle' => 'Un’esperienza da regalare',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Seleziona un servizio.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — metodo di pagamento (step 11 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_payment' => [
        'title' => 'AnimalAmo — Metodo di pagamento',
        'step' => 'Step 11 di 11',
        'heading' => 'Metodo di pagamento',
        'section' => 'Questi dati ti permetteranno di ricevere i pagamenti',
        'account_holder' => 'Titolare Conto',
        'iban' => 'IBAN',
        'bic' => 'BIC',
        'later' => 'Inserisci più tardi',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — foto (step 10 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_photos' => [
        'title' => 'AnimalAmo — Foto',
        'step' => 'Step 10 di 11',
        'heading' => 'Foto',
        'section' => 'Carica alcune foto',
        'helper' => 'Serviranno ad aumentare potenzialmente il tuo tasso di conversione in media del 2,7%, in altre parole, per aumentare le tue prenotazioni e i tuoi guadagni.',
        'drop' => 'Trascina qui le tue foto',
        'hint' => 'Devi caricare almeno 4 foto (7 o più consigliate)',
        'uploading' => 'Caricamento in corso…',
        'delete' => 'Elimina',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_min' => 'Devi caricare almeno 4 foto.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — smartbox (step 9 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_smartbox' => [
        'title' => 'AnimalAmo — Smartbox',
        'step' => 'Step 9 di 11',
        'heading' => 'Informazioni aggiuntive',
        'section' => 'Vuoi dare la possibilità alla tua struttura di essere inserita all’interno delle smartbox?',
        'helper' => 'Chiunque creerà una smartbox potrà inserire la tua struttura nella lista delle strutture. Così gli utenti che acquisteranno la smartbox potranno arrivare nella tua struttura.',
        'yes' => 'Si',
        'no' => 'No',
        'types_heading' => 'Seleziona il servizio che vuoi aderisca alle smartbox',
        'type_all' => 'Tutta la struttura',
        'type_overnight' => 'Pernottamento',
        'type_wellness' => 'Benessere',
        'type_wellness_desc' => 'Se presente una piscina, spa, etc che possono essere usate anche senza pernottamento',
        'type_adventure' => 'Avventura',
        'type_adventure_desc' => 'Se ci sono guide affiliate, degustazioni, etc',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — servizi animali (step 8 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_animal_services' => [
        'title' => 'AnimalAmo — Servizi animali',
        'step' => 'Step 8 di 11',
        'heading' => 'Servizi dedicati agli animali',
        'section' => 'Aggiungi le informazioni per descrivere i servizi che offri (puoi selezionare più di un’opzione)',
        'helper' => 'Aiuteranno l’utente a valutare la struttura.',
        'opt_none' => 'Nessuno',
        'opt_welcome' => 'Omaggio di benvenuto',
        'opt_welcome_desc' => 'Un regalo di omaggio per l’animale',
        'opt_petsitting' => 'Pet sitting',
        'opt_petsitting_desc' => 'Servizio di pet sitting',
        'opt_vet' => 'Servizio Veterinario',
        'opt_vet_desc' => 'Servizio interno alla struttura o nelle vicinanze',
        'opt_area' => 'Area Animali',
        'opt_area_desc' => 'Un’area dedicata agli animali',
        // Voci del catalogo amenity che nessuno slug raggiungeva (cliente, 26-27/09/2026).
        'opt_dogsitter' => 'Dog sitter',
        'opt_dogsitter_desc' => 'Qualcuno che si prende cura del cane in tua assenza',
        'opt_dog_beach' => 'Dog Beach nelle vicinanze',
        'opt_dog_beach_desc' => 'Una spiaggia che accoglie i cani, a poca distanza',
        'opt_surcharge' => 'Supplemento animali',
        'opt_surcharge_desc' => 'Per l’animale è previsto un costo aggiuntivo',
        'opt_dog_pool' => 'Piscina per cani',
        'opt_dog_pool_desc' => 'Una piscina in cui i cani possono fare il bagno',
        'opt_other' => 'Altro',
        'other_placeholder' => 'Descrivi il servizio',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — servizi struttura (step 7 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_services' => [
        'title' => 'AnimalAmo — Servizi struttura',
        'step' => 'Step 7 di 11',
        'heading' => 'Informazioni sui servizi della struttura',
        'section' => 'Aggiungi le informazioni per descrivere i servizi presenti (puoi selezionare più di un’opzione)',
        'helper' => 'Aiuteranno l’utente a valutare la struttura.',
        'additional_heading' => 'Servizi aggiuntivi presenti',
        'rules_heading' => 'Regole della struttura',
        'other_placeholder' => 'Descrivi il servizio',
        'time_from' => 'Ora inizio',
        'time_to' => 'Ora fine',
        'svc_ac' => 'Aria condizionata',
        'svc_heating' => 'Riscaldamento',
        'svc_wifi' => 'Wi-fi gratuito',
        'svc_ev' => 'Stazione di ricarica per veicoli elettrici',
        'svc_tv' => 'TV',
        'svc_pool' => 'Piscina',
        'svc_sauna' => 'Sauna',
        // Voci del catalogo amenity che nessuno slug raggiungeva (cliente, 26-27/09/2026).
        'svc_laundry' => 'Lavanderia',
        'svc_lift' => 'Ascensore',
        'svc_bike_rental' => 'Noleggio bici',
        'add_none' => 'Nessuno',
        'add_breakfast' => 'Colazione',
        'add_lunch' => 'Pranzo',
        'add_dinner' => 'Cena',
        'add_other' => 'Altro',
        'rule_no_smoking' => 'Vietato fumare',
        'rule_no_parties' => 'Vietato fare feste/eventi',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — cancellazione (step 6 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_cancellation' => [
        'title' => 'AnimalAmo — Cancellazione',
        'step' => 'Step 6 di 11',
        'heading' => 'Cancellazione',
        'section' => 'Quando può l’ospite cancellare la prenotazione gratis?',
        'helper' => 'Aiuteranno l’utente a scegliere la struttura.',
        'when' => 'Quando?',
        'free' => 'Offri la cancellazione gratuita',
        'pays' => 'L’ospite paga l’importo totale',
        'arrival' => 'Data di arrivo',
        'days_30' => '30 giorni',
        'days_15' => '15 giorni',
        'days_7' => '7 giorni',
        'days_1' => '1 giorno',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Seleziona quando è possibile cancellare gratis.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — informazioni stanze (step 5 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_rooms' => [
        'title' => 'AnimalAmo — Informazioni stanze',
        'step' => 'Step 5 di 11',
        'heading' => 'Informazioni sulle stanze',
        'section' => 'Aggiungi le informazioni che descrivono le stanze che offri',
        'helper' => 'Aiuteranno l’utente a valutare la struttura.',
        'room_type' => 'Tipologia Stanze',
        'room_count' => 'Numero di stanze',
        'price' => 'Prezzo',
        'type_single' => 'Singola',
        'type_double' => 'Doppia',
        'type_triple' => 'Tripla',
        'type_suite' => 'Suite',
        // Tipologia fittizia della riga unica in modalità alloggio intero
        // (HotelRoomsForm::WHOLE_PROPERTY_TYPE): finora nessuno la mostrava.
        'type_whole' => 'Alloggio intero',
        'add_rooms' => 'Aggiungi stanze',
        'checkin' => 'Check in',
        'checkout' => 'Check out',
        'from' => 'Dalle:',
        'to' => 'Alle:',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Compila le informazioni sulle stanze e gli orari di check-in/out.',

        // Variante "casa vacanza": si affitta l'alloggio intero, quindi niente
        // righe stanza ripetibili — una sola unità, posti letto e prezzo.
        'whole_heading' => 'Informazioni sull’alloggio',
        'whole_section' => 'Aggiungi le informazioni che descrivono l’alloggio che offri',
        'whole_helper' => 'La casa viene affittata per intero: indica quante persone ospita e il prezzo a notte.',
        'beds' => 'Posti letto',
        'whole_price' => 'Prezzo a notte',
        'beds_error' => 'Indica quanti posti letto ha l’alloggio.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — descrizione (step 4 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_description' => [
        'title' => 'AnimalAmo — Descrizione',
        'step' => 'Step 4 di 11',
        'heading' => 'Descrizione',
        'section' => 'Presenta il tuo servizio',
        'helper' => 'Dai al cliente un assaggio di ciò che farà in 2 o 3 frasi. Questa sarà la prima cosa che i clienti leggeranno dopo il titolo e li ispirerà a continuare.',
        'placeholder' => 'Descrizione',
        'chars' => 'caratteri',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Inserisci una descrizione.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — luogo (step 3 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_location' => [
        'title' => 'AnimalAmo — Luogo',
        'step' => 'Step 3 di 11',
        'heading' => 'Luogo',
        'section' => 'Aggiungi le informazioni che descrivono il tuo servizio',
        'helper' => 'Aiuteranno l’utente a capire meglio che servizio è.',
        'address' => 'Indirizzo',
        'city' => 'Città',
        'province' => 'Provincia',
        'zip' => 'Cap',
        'license' => 'Licenza apertura',
        'back' => 'Indietro',
        'next' => 'Avanti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Hotel — nome struttura (step 2 di 11)
    |--------------------------------------------------------------------------
    */
    'hotel_title' => [
        'title' => 'AnimalAmo — Nome struttura',
        'step' => 'Step 2 di 11',
        'heading' => 'Il nome della tua struttura',
        'section' => 'Qual’è il nome della tua struttura?',
        'helper' => 'Aiuterà gli utenti a trovare la tua struttura velocemente',
        'field_label' => 'Nome struttura',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Inserisci il nome della struttura.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipologia struttura (step 1 di 11 del flusso struttura ricettiva)
    |--------------------------------------------------------------------------
    */
    'structure_type' => [
        'title' => 'AnimalAmo — Tipologia struttura',
        'step' => 'Step 1 di 11',
        'hotel' => 'Hotel',
        'bb' => 'B&B',
        'agriturismo' => 'Agriturismo',
        // Chi affitta l'alloggio intero e non le singole camere: lo step 5
        // cambia di conseguenza (posti letto al posto delle righe stanza).
        'casa_vacanza' => 'Casa vacanza',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Seleziona una tipologia di struttura.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard B2B
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'joined' => 'Benvenuto su AnimalAmo! Il tuo account partner è attivo: crea il tuo primo servizio. Partita IVA, codice fiscale e indirizzo puoi aggiungerli quando vuoi dal profilo.',
        'complete_profile' => 'Completa il profilo: mancano :fields.',
        'complete_profile_cta' => 'Vai al profilo',
        'title' => 'AnimalAmo — Dashboard partner',
        // Neutro: su `users` non c'è il genere, e il "Benvenuta" del mockup
        // salutava al femminile anche i partner uomini.
        'welcome' => 'Ti diamo il benvenuto, :name',
        'intro' => 'Crea il tuo primo prodotto e condividi esperienze indimenticabili con milioni di viaggiatori.',
        'cta' => 'Crea il tuo primo servizio',
        'stat_sold' => 'Esperienze vendute',
        'stat_cancelled' => 'Esperienze cancellate',
        'stat_saved' => 'Esperienze salvate',
        // P4: servizi chiusi prima del collegamento Stripe, pubblicati in automatico dopo.
        'awaiting_stripe_banner' => ':count servizio è in attesa del collegamento Stripe: lo pubblicheremo appena il conto è attivo.|:count servizi sono in attesa del collegamento Stripe: li pubblicheremo appena il conto è attivo.',
        'awaiting_stripe_cta' => 'Collega Stripe',
        // Smartbox ferme perché manca l'incasso online: il titolo è la frase
        // che la cliente ha chiesto testualmente (27/09/2026).
        'smartbox_payment_heading' => 'Per pubblicare e vendere una Smartbox è necessario collegare il sistema di pagamento',
        'smartbox_payment_banner' => 'Hai :count Smartbox pronta che non possiamo mettere in vetrina: torna in vendita da sola appena ricevi i pagamenti online.|Hai :count Smartbox pronte che non possiamo mettere in vetrina: tornano in vendita da sole appena ricevi i pagamenti online.',
        'smartbox_payment_cta' => 'Collega il sistema di pagamento',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email invito candidatura ("Lavora con noi")
    |--------------------------------------------------------------------------
    */
    'invitation_mail' => [
        'subject' => 'AnimalAmo — Completa la tua iscrizione partner',
        'heading' => 'Ciao :name!',
        'intro' => 'Grazie per la candidatura di :business come partner AnimalAmo.',
        'cta_hint' => 'Completa la registrazione dal pulsante qui sotto: bastano due passaggi.',
        'cta' => 'Completa la registrazione',
        'outro' => 'Se non hai inviato tu questa richiesta, ignora pure questa email.',
        'signature' => 'A presto,',
    ],

    /*
    |--------------------------------------------------------------------------
    | Benvenuto al partner creato dal pannello
    |--------------------------------------------------------------------------
    */
    'welcome_mail' => [
        'subject' => 'Benvenuto su AnimalAmo: il tuo account partner è pronto',
        'title' => 'Benvenuto su AnimalAmo, :name!',
        'intro' => 'Abbiamo creato l’account partner di :business. Per entrare nella tua area scegli una password dal pulsante qui sotto.',
        'set_password_cta' => 'Scegli la password',
        'expires' => 'Il link è valido per :days giorni. Se scade, usa “Password dimenticata” nell’accesso partner.',
        'password_given_intro' => 'Abbiamo creato l’account partner di :business. Accedi con questa email e la password che ti abbiamo comunicato: potrai cambiarla quando vuoi da “Password dimenticata”.',
        'promoted_intro' => 'Il tuo account AnimalAmo ora è anche l’account partner di :business. Accedi con la tua email e la password di sempre per aprire l’area partner.',
        'login_cta' => 'Vai all’area partner',
    ],

];
