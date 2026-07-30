<?php

// Messaggi di validazione condivisi (form auth + pagine profilo). Le stringhe
// utente restano IDENTICHE a quelle mostrate finora; le varianti per-campo non
// esprimibili qui vivono negli override messages() dei singoli componenti.
return [
    'before' => ':Attribute deve essere precedente a :date.',
    'boolean' => 'Valore non valido.',
    'date' => 'Inserisci una data valida.',
    'date_format' => 'Usa il formato :format.',
    'digits' => ':Attribute deve avere :digits cifre.',
    'email' => 'Inserisci un indirizzo email valido.',
    'max' => [
        'string' => 'Valore troppo lungo.',
    ],
    'min' => [
        'string' => ':Attribute deve contenere almeno :min caratteri.',
    ],
    'phone' => 'Inserisci un numero di telefono valido.',
    'required' => 'Inserisci :attribute.',
    'same' => ':Attribute non coincide con :other.',
    'string' => 'Valore non valido.',
    'unique' => ':Attribute è già in uso.',

    // Testi su misura che il messaggio generico non può riprodurre.
    'custom' => [
        'birthDate' => [
            'before' => 'La data di nascita deve essere precedente a oggi.',
            'date_format' => 'Usa il formato gg/mm/aaaa.',
        ],
        'email' => [
            'unique' => 'Questa email è già registrata.',
        ],
        'passwordConfirmation' => [
            'required' => 'Ripeti la password.',
            'same' => 'Le password non coincidono.',
        ],
        'passwordConfirm' => [
            'required' => 'Conferma la nuova password.',
            'same' => 'Le password non coincidono.',
        ],
        // Dati fiscali del partner: "Valore troppo lungo." non dice quanto.
        'pec' => [
            'email' => 'Inserisci un indirizzo PEC valido.',
        ],
        'sdi' => [
            'max' => 'Il codice SDI è di 7 caratteri.',
        ],
        'taxCode' => [
            'max' => 'Il codice fiscale non può superare i 16 caratteri.',
        ],
        'vat' => [
            'max' => 'La partita IVA non può superare i 13 caratteri.',
        ],
    ],

    // Nomi campo con articolo, così "Inserisci :attribute." resta naturale.
    'attributes' => [
        'address' => 'l\'indirizzo',
        'birthDate' => 'la data di nascita',
        'businessName' => 'la ragione sociale',
        'city' => 'la città',
        'description' => 'una descrizione',
        'email' => 'l\'email',
        'firstName' => 'il nome',
        'lastName' => 'il cognome',
        'offerType' => 'il tipo di offerta',
        'password' => 'la password',
        'pec' => 'la PEC',
        'petType' => 'la tipologia di animale',
        'phone' => 'il numero di cellulare',
        'postalCode' => 'il CAP',
        'province' => 'la provincia',
        'recipientEmail' => 'l\'email del destinatario',
        'role' => 'il tuo ruolo',
        'sdi' => 'il codice SDI',
        'taxCode' => 'il codice fiscale',
        'vat' => 'la partita IVA',
        'website' => 'il sito web',
        'zip' => 'il CAP',
    ],
];
