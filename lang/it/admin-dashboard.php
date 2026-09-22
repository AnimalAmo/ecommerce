<?php

/*
| Pannello di amministrazione — Home e Ricerca globale. Solo italiano: il
| pannello non è localizzato, quindi niente lang/en/admin-dashboard.php (e il
| file non è in LangParityTest).
*/

return [
    // Sotto i numeri della home: le prenotazioni in struttura non sono in "Ordini del mese".
    'on_site_note' => '{1} In più :count prenotazione da pagare in struttura, per :amount: non entra negli incassi.|[2,*] In più :count prenotazioni da pagare in struttura, per :amount: non entrano negli incassi.',

    'home' => [
        'title' => 'Dashboard',

        // Il saluto cambia con l'ora italiana; il nome si aggiunge in coda solo se c'è.
        'greeting' => [
            'morning' => 'Buongiorno',
            'afternoon' => 'Buon pomeriggio',
            'evening' => 'Buonasera',
        ],
        'subtitle' => ':date — ecco cosa è arrivato dal sito.',

        'todo' => [
            'approvals' => '{1} scheda da approvare|[0,*] schede da approvare',
            'approvals_oldest' => '{0} la più vecchia è arrivata oggi|{1} la più vecchia da ieri|[2,*] la più vecchia da :count giorni',
            'approvals_none' => 'nessuna in attesa',
            'applications' => '{1} candidatura|[0,*] candidature',
            'applications_hint' => 'mai lavorate, dal modulo del sito',
            'messages' => '{1} messaggio|[0,*] messaggi',
            'messages_hint' => 'dal modulo contatti, da lavorare',
            'reviews' => '{1} recensione|[0,*] recensioni',
            'reviews_hint' => 'in attesa di moderazione',
            'posts' => '{1} post community|[0,*] post community',
            'posts_hint' => 'segnalati dagli utenti',
        ],

        'kpi' => [
            'catalog' => 'Schede pubblicate',
            'catalog_note' => '{0} nessuna sospesa|{1} :count sospesa|[2,*] :count sospese',
            'users' => 'Iscritti',
            'users_note' => '{0} nessuno negli ultimi 30 giorni|[1,*] +:count negli ultimi 30 giorni',
            'orders' => 'Ordini del mese',
            'orders_note' => ':amount incassati',
            'partners' => 'Partner attivi',
            'partners_note' => '{0} tutti con almeno una scheda|[1,*] :count senza schede',
        ],

        'latest' => [
            'heading' => 'Ultime schede pubblicate',
            'link' => 'Vedi catalogo',
            'empty' => 'Nessuna scheda a catalogo.',
            'meta' => ':partner · :when',
        ],

        'inbox' => [
            'heading' => 'Arrivato dal sito',
            'link' => 'Vedi tutto',
            'empty' => 'Niente di nuovo dal sito.',
            'kind_application' => 'Candidatura',
            'kind_message' => 'Contatto',
            'application_summary' => ':business — :offer, :city',
        ],
    ],

    'search' => [
        'title' => 'Cerca',
        'heading' => 'Risultati per “:term”',
        'heading_empty' => 'Cerca nel pannello',
        'hint' => 'Scrivi almeno :min caratteri nel campo in alto: cerco fra schede, iscritti e numeri d\'ordine.',
        'summary' => '{0} Nessun risultato fra schede, iscritti e ordini.|{1} Un risultato.|[2,*] :count risultati.',
        'shown' => 'primi :shown di :total',

        'catalog' => [
            'heading' => 'Schede',
            'all' => 'Vedi tutte nel catalogo',
            'empty' => 'Nessuna scheda con questo nome, luogo o partner.',
            'col_item' => 'Scheda',
            'col_partner' => 'Partner',
            'col_type' => 'Tipo',
            'col_status' => 'Stato',
        ],

        'users' => [
            'heading' => 'Iscritti',
            'all' => 'Vedi tutti fra gli iscritti',
            'empty' => 'Nessun iscritto con questo nome o email.',
            'col_user' => 'Utente',
            'col_role' => 'Ruolo',
            'col_since' => 'Iscritto il',
            'role_partner' => 'Partner',
            'role_client' => 'Cliente',
            'anonymized' => 'Anonimizzato',
        ],

        'orders' => [
            'heading' => 'Ordini',
            'empty' => 'Nessun ordine con questo numero.',
            'empty_no_digits' => 'Gli ordini si cercano per numero (es. ORD-000042 o 42).',
            'col_order' => 'Ordine',
            'col_buyer' => 'Cliente',
            'col_date' => 'Data',
            'col_total' => 'Totale',
            'col_status' => 'Stato',
            'guest' => 'senza account',
        ],
    ],
];
