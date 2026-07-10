<?php

return [

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
    'title_thanks' => 'AnimalAmo — Grazie',

    // Intestazione
    'heading' => 'Iscrivi la tua struttura o un’attività pet friendly',
    'intro' => 'Siamo sempre aperti ad ampliare le possibilità per le persone che scelgono Animal-amo. Proponi la tua realtà e collabora con noi!',

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
    'submit' => 'Invia',

    // Pagina ringraziamento
    'thanks_heading' => 'Grazie!',
    'thanks_line_1' => 'La tua richiesta è stata inoltrata correttamente.',
    'thanks_line_2' => 'Ti risponderemo il prima possibile.',
    'back_home' => 'Torna alla Home',

    /*
    |--------------------------------------------------------------------------
    | Chrome B2B (header + footer dell'area partner)
    |--------------------------------------------------------------------------
    */
    'nav_help' => 'Aiuto',
    'nav_dashboard' => 'Dashboard',
    'nav_create_service' => 'Crea servizio',
    'nav_my_services' => 'I miei servizi',
    'nav_bookings' => 'Prenotazioni',
    'nav_profile' => 'Profilo',
    'help_contact' => 'Contattaci',
    'help_support' => 'Assistenza',
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
        'pec' => 'PEC',
        'sdi' => 'SDI',
        'back' => 'Indietro',
        'next' => 'Prosegui',
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
        'attivita_title' => 'Attività ed Eventi',
        'attivita_subtitle' => 'Come una gita di un giorno, un ritrovo con i propri animali',
        'servizi_title' => 'Servizi',
        'servizi_subtitle' => 'Come Pet sitting, Addestramento, altro',
        'submit' => 'Crea un account',
        'error_required' => 'Seleziona almeno un servizio.',
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
        'attivita_title' => 'Attività ed Eventi',
        'attivita_subtitle' => 'Come una gita di un giorno, un ritrovo con i propri animali',
        'servizi_title' => 'Servizi',
        'servizi_subtitle' => 'Come Pet sitting, Addestramento, altro',
        'smartbox_title' => 'Smartbox',
        'smartbox_subtitle' => 'Un’esperienza da regalare',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Seleziona un servizio.',
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
        'title' => 'AnimalAmo — Dashboard partner',
        'welcome' => 'Benvenuta :name',
        'intro' => 'Crea il tuo primo prodotto e condividi esperienze indimenticabili con milioni di viaggiatori.',
        'cta' => 'Crea il tuo primo servizio',
        'stat_sold' => 'Esperienze vendute',
        'stat_cancelled' => 'Esperienze cancellate',
        'stat_saved' => 'Esperienze salvate',
    ],

];
