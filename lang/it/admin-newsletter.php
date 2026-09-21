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
    ],

];
