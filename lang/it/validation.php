<?php

// Messaggi di validazione condivisi (form auth + pagine profilo). Le stringhe
// utente restano IDENTICHE a quelle mostrate finora; le varianti per-campo non
// esprimibili qui vivono negli override messages() dei singoli componenti.
return [
    'before' => ':Attribute deve essere precedente a :date.',
    'boolean' => 'Valore non valido.',
    'date' => 'Inserisci una data valida.',
    'date_format' => 'Usa il formato :format.',
    'email' => 'Inserisci un indirizzo email valido.',
    'max' => [
        'string' => 'Valore troppo lungo.',
    ],
    'min' => [
        'string' => ':Attribute deve contenere almeno :min caratteri.',
    ],
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
    ],

    // Nomi campo con articolo, così "Inserisci :attribute." resta naturale.
    'attributes' => [
        'address' => 'l\'indirizzo',
        'birthDate' => 'la data di nascita',
        'city' => 'la città',
        'email' => 'l\'email',
        'firstName' => 'il nome',
        'lastName' => 'il cognome',
        'password' => 'la password',
        'petType' => 'la tipologia di animale',
        'phone' => 'il numero di cellulare',
        'postalCode' => 'il CAP',
        'recipientEmail' => 'l\'email del destinatario',
        'zip' => 'il CAP',
    ],
];
