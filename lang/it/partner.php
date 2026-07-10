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
    | Profilo partner (info personali / metodo di pagamento / sicurezza)
    |--------------------------------------------------------------------------
    */
    'profile' => [
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
        'email' => 'Email',
        'address' => 'Indirizzo',
        'province' => 'Provincia',
        'city' => 'Città',
        'zip' => 'Cap',
        'vat' => 'Partita IVA',
        'phone' => 'Cellulare',
        'tax_code' => 'Codice Fiscale',
        'pec' => 'PEC',
        'sdi' => 'SDI',

        'payment_title' => 'AnimalAmo — Metodo di pagamento',
        'payment_heading' => 'Metodo di pagamento',
        'account_holder' => 'Titolare Conto',
        'iban' => 'IBAN',
        'bic' => 'BIC',

        'security_title' => 'AnimalAmo — Sicurezza',
        'security_heading' => 'Sicurezza',
        'current_password' => 'Password attuale',
        'new_password' => 'Nuova password',
        'confirm_password' => 'Conferma password',
        'privacy_settings' => 'Impostazioni sulla Privacy',
        'delete_account' => 'Elimina Account',
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
        'load_more' => 'Carica altro',
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
        'sdi' => 'SDI',
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
        'add_rooms' => 'Aggiungi stanze',
        'checkin' => 'Check in',
        'checkout' => 'Check out',
        'from' => 'Dalle:',
        'to' => 'Alle:',
        'back' => 'Indietro',
        'next' => 'Avanti',
        'error_required' => 'Compila le informazioni sulle stanze e gli orari di check-in/out.',
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
