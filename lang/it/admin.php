<?php

/*
| Testi condivisi del pannello di amministrazione: guscio (layout, navigazione),
| accesso e azioni comuni. Solo italiano: il pannello non è localizzato, quindi
| il file non ha la controparte in lang/en e non è in LangParityTest.
| Ogni modulo ha il suo file (admin-catalog, admin-people, …).
*/

return [
    'brand' => 'Amministrazione AnimalAmo',
    'section' => 'Amministrazione',
    'logo_alt' => 'AnimalAmo',
    'none' => '—',

    'layout' => [
        'open_menu' => 'Apri menu',
        'close_menu' => 'Chiudi menu',
        'nav_label' => 'Pannello',
        'public_site' => 'Vai al sito pubblico',
        'logout' => 'Esci',
        'search_placeholder' => 'Cerca schede, iscritti, ordini…',
        'search_label' => 'Cerca nel pannello',
    ],

    'nav' => [
        'groups' => [
            'overview' => 'Panoramica',
            'catalog' => 'Catalogo',
            'content' => 'Contenuti',
            'people' => 'Persone',
            'money' => 'Denaro',
        ],
        'home' => 'Home pannello',
        'catalog' => 'Schede pubblicate',
        'approvals' => 'Da approvare',
        'reviews' => 'Recensioni',
        'pages' => 'Pagine',
        'articles' => 'Animal Times',
        'faqs' => 'Domande frequenti',
        'community' => 'Community',
        'users' => 'Iscritti',
        'inbox' => 'Contatti e candidature',
        'newsletter' => 'Newsletter',
        'payouts' => 'Incassi',
    ],

    'actions' => [
        'cancel' => 'Annulla',
        'back' => 'Indietro',
    ],

    'auth' => [
        'email' => 'Email',
        'password' => 'Password',

        'login' => [
            'title' => 'Accesso',
            'heading' => 'Area di amministrazione',
            'intro' => 'Accedi con le credenziali che ti abbiamo assegnato.',
            'remember' => 'Resta collegato',
            'forgot' => 'Hai dimenticato la password?',
            'submit' => 'Entra',
        ],

        'forgot' => [
            'title' => 'Password dimenticata',
            'heading' => 'Password dimenticata',
            'intro' => "Inserisci l'indirizzo email del tuo account: ti mandiamo un link per scegliere una nuova password.",
            'submit' => 'Invia il link',
            'sent_heading' => 'Controlla la posta',
            'sent_body' => 'Se :email è un account del pannello, il link per reimpostare la password è in arrivo.',
            'back_to_login' => "Torna all'accesso",
            'sent_note' => 'Il link vale :minutes minuti. Se non lo trovi, guarda nella cartella dello spam.',
        ],

        'reset' => [
            'title' => 'Nuova password',
            'heading' => 'Scegli una nuova password',
            'intro' => 'Stai reimpostando la password di :email.',
            'password' => 'Nuova password',
            'password_confirmation' => 'Ripeti la password',
            'rules' => 'Almeno dieci caratteri, con una lettera maiuscola e un numero.',
            'submit' => 'Salva la password',
            'invalid_heading' => 'Link non più valido',
            'invalid_body' => 'Il link è scaduto o è già stato usato. Richiedine uno nuovo e riprova.',
            'invalid_action' => 'Chiedi un nuovo link',
            'done_heading' => 'Password aggiornata',
            'done_body' => 'Puoi accedere al pannello con la nuova password.',
            'done_action' => 'Vai al pannello',
        ],

        'errors' => [
            'throttled' => 'Troppi tentativi. Riprova fra :minutes minuti.',
            'failed' => "Email o password non corretti. Dopo cinque tentativi l'accesso si blocca per quindici minuti.",
            'reset_throttled' => 'Troppe richieste. Riprova fra un minuto.',
            'password_required' => 'Scegli una password.',
            'password_confirmed' => 'Le due password non coincidono.',
            'password_min' => 'Almeno dieci caratteri.',
            'password_mixed' => 'Serve almeno una lettera maiuscola e una minuscola.',
            'password_numbers' => 'Serve almeno un numero.',
        ],
    ],
];
