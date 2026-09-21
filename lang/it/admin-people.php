<?php

/*
| Pannello di amministrazione — Persone (iscritti, contatti e candidature) e
| Recensioni. Solo italiano: il pannello non è localizzato, quindi niente
| lang/en/admin-people.php (e il file non è in LangParityTest).
*/

return [
    'role' => [
        'client' => 'Cliente',
        'partner' => 'Partner',
    ],

    'users' => [
        'title' => 'Iscritti',
        'subtitle_users' => '{0} Nessun utente registrato.|{1} :count utente registrato.|[2,*] :count utenti registrati.',
        'subtitle_newsletter' => '{0} Nessuno ha chiesto la newsletter.|{1} :count ha chiesto la newsletter.|[2,*] :count hanno chiesto la newsletter.',
        'export' => 'Esporta in Excel',

        'search' => 'Cerca per nome o email',
        'filter_newsletter' => 'Newsletter',
        'filter_status' => 'Stato',
        'filter_role' => 'Ruolo',
        'filter_period' => 'Periodo di iscrizione',
        'newsletter_all' => 'Tutti gli iscritti',
        'newsletter_with' => 'Con newsletter',
        'newsletter_without' => 'Senza newsletter',
        'status_all' => 'Tutti gli stati',
        'role_all' => 'Tutti i ruoli',
        'role_clients' => 'Clienti',
        'role_partners' => 'Partner',
        'period_always' => 'Iscritti: sempre',
        'period_30d' => 'Ultimi 30 giorni',
        'period_year' => 'Quest\'anno',
        'empty' => 'Nessun iscritto corrisponde ai filtri.',

        'col_user' => 'Utente',
        'col_since' => 'Iscritto il',
        'col_newsletter' => 'Newsletter',
        'col_orders' => 'Ordini',
        'col_spent' => 'Speso',
        'col_status' => 'Stato',
        'col_actions' => 'Azioni',

        'open' => 'Apri scheda',
        'anonymize' => 'Cancella su richiesta',

        'status' => [
            'active' => 'Attivo',
            'inactive' => 'Disattivato',
            'anonymized' => 'Anonimizzato',
        ],

        'newsletter' => [
            'confirmed' => 'Sì',
            'pending' => 'In attesa',
            'none' => 'No',
        ],

        'export_columns' => [
            'first_name' => 'Nome',
            'last_name' => 'Cognome',
            'email' => 'Email',
            'role' => 'Ruolo',
            'since' => 'Iscritto il',
            'newsletter' => 'Newsletter',
            'orders' => 'Ordini pagati',
            'spent' => 'Speso (€)',
            'status' => 'Stato',
        ],
        'export_filename' => 'iscritti-:date.csv',

        // Scheda iscritto
        'back' => 'Torna agli iscritti',
        'deactivate' => 'Disattiva account',
        'reactivate' => 'Riattiva account',
        'deactivated' => 'Account disattivato.',
        'reactivated' => 'Account riattivato.',
        'anonymized_heading' => 'Dati cancellati su richiesta',
        'anonymized_body' => 'I dati personali di questo account sono stati cancellati il :date. Restano gli ordini, per gli obblighi fiscali. L\'account non può più accedere.',
        'profile' => 'Profilo',
        'account' => 'Account',
        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'email' => 'Email',
        'phone' => 'Telefono',
        'birth_date' => 'Data di nascita',
        'address' => 'Indirizzo',
        'role_label' => 'Ruolo',
        'status_label' => 'Stato',
        'since' => 'Iscritto il',
        'newsletter_label' => 'Newsletter',
        'marketing' => 'Consenso marketing',
        'yes' => 'Sì',
        'no' => 'No',
        'newsletter_state' => [
            'confirmed' => 'Iscritto',
            'pending' => 'In attesa di conferma',
            'unsubscribed' => 'Disiscritto',
            'none' => 'No',
            'suppressed' => 'Non recapitabile',
        ],
        'active_hint' => 'Un account disattivato non entra nell\'area partner. Per chiudere del tutto un account usa "Cancella su richiesta".',
        'orders' => 'Ordini',
        'orders_summary' => '{0} Nessun ordine|{1} :count ordine · pagati :paid|[2,*] :count ordini · pagati :paid',
        'orders_empty' => 'Nessun ordine.',
        'order_number' => 'Numero',
        'order_date' => 'Data',
        'order_total' => 'Totale',
        'order_status' => 'Stato',
        'order_items' => 'Prenotazioni',
        'order_statuses' => [
            'paid' => 'Pagato',
            'pending' => 'In attesa',
            'cancelled' => 'Annullato',
        ],
        'partner' => 'Partner',
        'business_name' => 'Attività',
        'listings' => 'Schede a catalogo',
        'listings_count' => '{0} Nessuna scheda|{1} :count scheda|[2,*] :count schede',
        'listings_suspended' => '{1} :count sospesa|[2,*] :count sospese',
        'listings_open' => 'Vedi le schede',
        'bookings_received' => 'Prenotazioni ricevute',
        'bookings_count' => '{0} Nessuna prenotazione pagata|{1} :count prenotazione pagata|[2,*] :count prenotazioni pagate',
        'pets' => 'Animali',
        'pets_empty' => 'Nessun animale registrato.',
        'applications' => 'Candidature partner',
        'applications_empty' => 'Nessuna candidatura.',

        'errors' => [
            'anonymized' => 'Un account anonimizzato non si può riattivare.',
            'superadmin' => 'Gli amministratori del pannello non si gestiscono da qui.',
        ],
    ],

    'anonymize' => [
        'title' => 'Cancellare questo contatto?',
        'body' => 'L\'indirizzo :email e i dati personali di :name verranno cancellati: profilo, animali, preferiti, carrello, carta salvata e iscrizione alla newsletter. I post e le recensioni restano, senza il suo nome.',
        'orders_kept' => 'Gli ordini restano intatti: vanno conservati per gli obblighi fiscali.',
        'cancel' => 'Annulla',
        'confirm' => 'Cancella i dati',
        'done' => 'Dati personali cancellati. Gli ordini restano in archivio.',
        'blocked' => [
            'already' => 'I dati di questo utente sono già stati cancellati.',
            'superadmin' => 'Un amministratore del pannello non si può anonimizzare da qui.',
            'partner' => 'È un partner attivo con schede a catalogo: sospendi prima le sue schede e disattiva l\'account, poi potrai cancellare i suoi dati.',
        ],
    ],

    'application_status' => [
        'pending' => 'Da invitare',
        'invited' => 'Invitato',
        'registered' => 'Registrato',
    ],

];
