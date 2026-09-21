<?php

/*
| Pannello di amministrazione, modulo Newsletter: solo italiano (il pannello
| sta fuori dalla localizzazione), per questo non è in LangParityTest.
| I testi che arrivano agli iscritti stanno in lang/{it,en}/newsletter.php.
*/

return [

    'title' => 'Newsletter',

    'common' => [
        'cancel' => 'Annulla',
        'close' => 'Chiudi',
    ],

    'index' => [
        'confirmed_count' => '{1} :count iscritto confermato.|[0,*] :count iscritti confermati.',
        'last_sent' => 'L\'ultimo invio è del :date.',
        'never_sent' => 'Nessun invio finora.',
        'write' => 'Scrivi una newsletter',
    ],

    // Contatti della vecchia casella di registrazione, senza prova del consenso.
    'legacy' => [
        'heading' => '{1} :count contatto della vecchia casella di registrazione non ha la prova del consenso|[0,*] :count contatti della vecchia casella di registrazione non hanno la prova del consenso',
        'text' => 'Sono le spunte messe in registrazione prima della newsletter, senza data né testo accettato. Puoi mandare loro una mail di conferma: chi risponde entra in lista.',
        'button' => 'Manda la conferma',
        'modal_title' => 'Mandare la mail di conferma?',
        'modal_body' => '{1} :count contatto riceverà una mail con il link di conferma. Se clicca entra in lista con data, ora e testo accettato; se non risponde resta fuori.|[0,*] :count contatti riceveranno una mail con il link di conferma. Chi clicca entra in lista con data, ora e testo accettato; chi non risponde resta fuori.',
        'modal_note' => 'È un invio una volta sola: chi non conferma non verrà ricontattato.',
        'modal_confirm' => 'Manda le conferme',
        'done' => '{0} Nessuna mail da mandare: le conferme erano già partite.|{1} Mail di conferma in partenza per :count contatto.|[2,*] Mail di conferma in partenza per :count contatti.',
    ],

    'kpi' => [
        'confirmed' => 'Iscritti confermati',
        'confirmed_note' => '+:count questo mese',
        'pending' => 'In attesa di conferma',
        'pending_note' => 'hanno ricevuto la mail di conferma',
        'pending_note_legacy' => 'di cui :count dalla vecchia casella',
        'opens' => 'Aperture ultimo invio',
        'opens_note' => ':opened su :base consegnate',
        'opens_note_sent' => ':opened su :base inviate',
        'opens_none' => 'nessun invio',
        'unsubscribed' => 'Disiscritti',
        'unsubscribed_note' => 'negli ultimi 90 giorni',
    ],

    'subscribers' => [
        'heading' => 'Iscritti alla lista',
        'export' => 'Esporta',
        'search' => 'Cerca un indirizzo',
        'filter_status' => 'Stato',
        'filter_source' => 'Origine',
        'filter_locale' => 'Lingua',
        'all_statuses' => 'Tutti gli stati',
        'all_sources' => 'Tutte le origini',
        'all_locales' => 'Tutte le lingue',
        'col_address' => 'Indirizzo',
        'col_consent' => 'Consenso',
        'col_actions' => 'Azioni',
        'view_proof' => 'Vedi la prova del consenso',
        'unsubscribe' => 'Disiscrivi',
        'empty' => 'Nessun iscritto, per ora.',
        'empty_filtered' => 'Nessun iscritto corrisponde ai filtri.',
        'shown' => ':shown di :total iscritti',
    ],

    'status' => [
        'confirmed' => 'Confermato',
        'pending' => 'In attesa',
        'unsubscribed' => 'Disiscritto',
        'bounced' => 'Rimbalzato',
        'complained' => 'Segnalato come spam',
    ],

    'status_filter' => [
        'confirmed' => 'Confermati',
        'pending' => 'In attesa',
        'unsubscribed' => 'Disiscritti',
        'suppressed' => 'Rimbalzi e spam',
    ],

    'source' => [
        'footer' => 'piede del sito',
        'registration' => 'registrazione',
        'profile' => 'profilo',
        'legacy' => 'vecchia casella di registrazione',
        'admin' => 'pannello',
    ],

    'locale' => [
        'it' => 'italiano',
        'en' => 'inglese',
    ],

    // Seconda riga della colonna "Consenso".
    'proof_line' => [
        'confirmed' => 'confermato il :date',
        'pending_sent' => 'conferma inviata il :date',
        'no_proof' => 'nessuna prova',
        'unsubscribed' => 'disiscritto il :date',
        'bounced' => 'rimbalzato il :date',
        'complained' => 'segnalato il :date',
    ],

    'proof' => [
        'title' => 'Prova del consenso',
        'legacy_note' => 'Contatto della vecchia casella di registrazione: la spunta non ha una prova. Se conferma, la prova è il clic sul link della mail.',
        'email' => 'Indirizzo',
        'status' => 'Stato',
        'source' => 'Origine',
        'locale' => 'Lingua',
        'account' => 'Account sul sito',
        'account_yes' => 'sì, :name',
        'requested' => 'Richiesta',
        'consent_text' => 'Testo accettato',
        'request_ip' => 'IP della richiesta',
        'request_ua' => 'Browser della richiesta',
        'confirmation_sent' => 'Mail di conferma inviata',
        'confirmed' => 'Confermata',
        'confirm_ip' => 'IP della conferma',
        'confirm_ua' => 'Browser della conferma',
        'unsubscribed' => 'Disiscritto',
        'suppressed' => 'In lista di soppressione',
    ],

    'unsubscribe_modal' => [
        'title' => 'Disiscrivere questo indirizzo?',
        'body' => ':email non riceverà più la newsletter. Se ha un account, resta registrato come utente del sito.',
        'confirm' => 'Disiscrivi',
        'done' => ':email non riceverà più la newsletter.',
    ],

    'campaigns' => [
        'heading' => 'Invii',
        'col_subject' => 'Oggetto',
        'col_sent' => 'Inviata',
        'col_opens' => 'Aperture',
        'col_status' => 'Stato',
        'empty' => 'Nessuna newsletter, per ora.',
        'no_subject' => '(senza oggetto)',
        'progress' => ':sent su :total',
    ],

    'campaign_status' => [
        'draft' => 'Bozza',
        'sending' => 'In invio',
        'sent' => 'Inviata',
        'failed' => 'Non riuscita',
    ],

    'audience' => [
        'all' => 'tutti i confermati',
        'it' => 'solo italiano',
        'en' => 'solo inglese',
    ],

    // "Scrivi una newsletter".
    'editor' => [
        'back' => 'Torna alla newsletter',
        'title' => 'Newsletter',
        'title_new' => 'Scrivi una newsletter',
        'saved_at' => 'Bozza salvata alle :time',
        'not_saved' => 'Bozza non ancora salvata',
        'goes_to' => '{1} andrà a :count indirizzo confermato|[0,*] andrà a :count indirizzi confermati',
        'preview' => 'Anteprima',
        'save' => 'Salva bozza',
        'saved' => 'Bozza salvata.',
        'send_test' => 'Invia una prova',
        'send_all' => 'Invia a tutti',
        'tab_it' => 'Italiano',
        'tab_en' => 'English',
        'english_hint' => 'Facoltativa. Gli iscritti in inglese ricevono questa versione se oggetto e testo sono compilati, altrimenti quella italiana.',
        'subject' => 'Oggetto',
        'preheader' => 'Anteprima nella casella',
        'preheader_hint' => 'La riga grigia che la casella di posta mostra accanto all\'oggetto.',
        'body' => 'Testo',
        'recipients' => 'Destinatari',
        'audience' => 'Lista',
        'audience_option' => [
            'all' => 'Tutti i confermati (:count)',
            'it' => 'Solo italiano (:count)',
            'en' => 'Solo inglese (:count)',
        ],
        'rate' => 'Invio a scaglioni',
        'rate_option' => ':rate ogni ora',
        'rate_all' => 'Tutti subito',
        'rate_hint' => 'A :rate all\'ora l\'invio si chiude :duration.',
        'rate_hint_all' => 'Le mail partono tutte subito, a lotti di cento.',
        'bounce_hint' => 'Gli indirizzi che rimbalzano escono dalla lista da soli.',
        'checklist' => 'Prima di inviare',
        'test_title' => 'Invia una prova',
        'test_text' => 'La mail arriva com\'è, con un avviso in testa: controlla testo, link e aspetto prima di inviare a tutti.',
        'test_email' => 'Indirizzo',
        'test_locale' => 'Versione',
        'test_submit' => 'Invia la prova',
        'test_sent' => 'Prova inviata a :email.',
        'launch_title' => '{1} Inviare a :count indirizzo?|[0,*] Inviare a :count indirizzi?',
        'launch_body' => 'La newsletter parte a scaglioni di :rate all\'ora e si chiude :duration. Una volta partita non si può fermare a metà.',
        'launch_body_all' => 'La newsletter parte subito a tutti. Una volta partita non si può fermare a metà.',
        'launch_confirm' => 'Invia adesso',
        'launched' => 'Newsletter in partenza.',
        'resumed' => 'Invio ripreso.',
        'preview_title' => 'Anteprima',
        'attributes' => [
            'subject_it' => 'oggetto in italiano',
            'subject_en' => 'oggetto in inglese',
            'preheader_it' => 'anteprima nella casella in italiano',
            'preheader_en' => 'anteprima nella casella in inglese',
            'body_it' => 'testo in italiano',
            'body_en' => 'testo in inglese',
        ],
    ],

    'duration' => [
        'now' => 'subito',
        'minutes' => '{1} in circa un minuto|[0,*] in circa :count minuti',
        'hours' => '{1} in circa un\'ora|[0,*] in circa :count ore',
    ],

    'checklist' => [
        'unsubscribe_link' => 'Il link per disiscriversi è nel piede della mail',
        'test_missing' => 'Manda una prova e controllala prima di inviare a tutti',
        'test_outdated' => 'Il testo è cambiato dopo la prova inviata a :email: mandane un\'altra',
        'test_done' => 'Prova inviata a :email e controllata',
        'english_done' => 'Versione inglese compilata',
        'english_missing' => 'Manca la versione inglese: gli iscritti in inglese la riceveranno in italiano',
        'english_not_needed' => 'Solo italiano: la versione inglese non serve',
        'dmarc_checking' => 'Controllo del record DMARC su :domain…',
        'dmarc_done' => 'Record DMARC presente su :domain',
        'dmarc_missing' => 'Manca il record DMARC su :domain: incide su quante mail arrivano in casella',
    ],

    // Campagna partita, in sola lettura.
    'report' => [
        'sub' => ':status · partita il :date',
        'recipients' => 'Destinatari',
        'sent' => 'Spedite',
        'finished' => 'chiuso il :date',
        'delivered' => 'Consegnate',
        'delivered_note' => 'confermate da Mailgun',
        'opens' => 'Aperture',
        'opens_note' => ':count indirizzi l\'hanno aperta',
        'failed' => 'Non partite',
        'failed_note' => 'errori di invio',
        'stalled_heading' => 'L\'invio sembra fermo',
        'stalled_text' => 'Nessuna mail partita dalle :time. Di solito è la coda di invio del server che si è fermata: riprendendo, chi ha già ricevuto la newsletter non la riceve una seconda volta.',
        'resume' => 'Riprendi l\'invio',
    ],

    // Intestazioni del CSV degli iscritti.
    'export' => [
        'email' => 'Indirizzo',
        'status' => 'Stato',
        'locale' => 'Lingua',
        'source' => 'Origine',
        'requested' => 'Richiesta il',
        'consent_text' => 'Testo accettato',
        'request_ip' => 'IP richiesta',
        'request_ua' => 'Browser richiesta',
        'confirmation_sent' => 'Conferma inviata il',
        'confirmed' => 'Confermato il',
        'confirm_ip' => 'IP conferma',
        'confirm_ua' => 'Browser conferma',
        'unsubscribed' => 'Disiscritto il',
        'suppressed' => 'Soppresso il',
    ],

    // Comandi artisan del modulo (newsletter:confirm-legacy, newsletter:resume).
    'command' => [
        'legacy_imported' => 'Contatti della vecchia casella aggiunti alla lista come da confermare: :count.',
        'legacy_pending' => 'Contatti della vecchia casella mai contattati: :count.',
        'legacy_dry_run' => 'Prova a vuoto: nessuna mail spedita. Rilancia senza --dry-run per mandare le conferme.',
        'legacy_sent' => 'Mail di conferma di cortesia messe in coda: :count. Chi non conferma non viene ricontattato.',
        'resume_not_sending' => 'Nessuna campagna in invio con questo id.',
        'resume_alive' => 'L\'invio sembra ancora in corso (ultima attività alle :time): riprenderlo raddoppierebbe il ritmo. Usa --force se sei sicuro che sia fermo.',
        'resume_done' => 'Invio ripreso: :count destinatari ancora da spedire.',
    ],

    // Errori mostrati nel pannello.
    'errors' => [
        'not_draft' => 'Questa newsletter è già partita: non si può inviare di nuovo.',
        'missing_italian' => 'Manca la versione italiana: oggetto e testo sono obbligatori.',
        'empty_audience' => 'Nessun iscritto confermato riceverebbe questa newsletter.',
        'shared_mailer' => 'Invio bloccato: la newsletter partirebbe dallo stesso dominio delle conferme di prenotazione, e chi la segnalasse come spam non riceverebbe più nemmeno quelle. Chiedi all\'assistenza tecnica di configurare il dominio della newsletter.',
        'inline_queue' => 'Invio bloccato: il sito non è pronto a spedire a scaglioni, e la lista partirebbe tutta insieme dentro questa pagina. Chiedi all\'assistenza tecnica di attivare la coda di invio.',
        'test_failed' => 'La prova non è partita: il servizio di invio ha rifiutato la mail. Riprova tra qualche minuto.',
    ],

];
