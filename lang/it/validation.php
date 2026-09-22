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
        'array' => 'Puoi sceglierne al massimo :max.',
        'file' => 'Il file non può superare :max KB.',
        'numeric' => 'Il valore non può superare :max.',
        'string' => 'Valore troppo lungo.',
    ],
    'min' => [
        'array' => 'Scegline almeno :min.',
        'file' => 'Il file deve pesare almeno :min KB.',
        'numeric' => 'Il valore deve essere almeno :min.',
        'string' => ':Attribute deve contenere almeno :min caratteri.',
    ],
    'phone' => 'Inserisci un numero di telefono valido.',
    'required' => 'Inserisci :attribute.',
    'same' => ':Attribute non coincide con :other.',
    'string' => 'Valore non valido.',
    'unique' => ':Attribute è già in uso.',

    // Regole usate dal pannello di amministrazione e dai form più recenti:
    // senza, Laravel ricadeva sul testo inglese del framework.
    'accepted' => 'Per continuare devi accettare.',
    'after' => 'Inserisci una data successiva a :date.',
    'after_or_equal' => 'Inserisci una data uguale o successiva a :date.',
    'alpha_dash' => 'Usa solo lettere, numeri, trattini e trattini bassi.',
    'array' => 'Valore non valido.',
    'before_or_equal' => 'Inserisci una data uguale o precedente a :date.',
    'between' => [
        'array' => 'Scegli fra :min e :max elementi.',
        'file' => 'Il file deve pesare fra :min e :max KB.',
        'numeric' => 'Inserisci un valore fra :min e :max.',
        'string' => 'Scrivi fra :min e :max caratteri.',
    ],
    'confirmed' => 'I due valori non coincidono.',
    'decimal' => 'Usa :decimal cifre decimali.',
    'dimensions' => 'Le dimensioni dell\'immagine non sono valide.',
    'distinct' => 'Questo valore è ripetuto.',
    'ends_with' => 'Deve finire con: :values.',
    'enum' => 'Valore non valido.',
    'exists' => 'Valore non valido.',
    'file' => 'Carica un file.',
    'filled' => 'Questo campo non può essere vuoto.',
    'gt' => [
        'numeric' => 'Inserisci un valore maggiore di :value.',
        'string' => 'Scrivi più di :value caratteri.',
    ],
    'gte' => [
        'numeric' => 'Inserisci un valore uguale o maggiore di :value.',
        'string' => 'Scrivi almeno :value caratteri.',
    ],
    'image' => 'Il file deve essere un\'immagine.',
    'in' => 'Valore non valido.',
    'integer' => 'Inserisci un numero intero.',
    'lowercase' => 'Usa solo lettere minuscole.',
    'lt' => [
        'numeric' => 'Inserisci un valore minore di :value.',
        'string' => 'Scrivi meno di :value caratteri.',
    ],
    'lte' => [
        'numeric' => 'Inserisci un valore uguale o minore di :value.',
        'string' => 'Scrivi al massimo :value caratteri.',
    ],
    'mimes' => 'Formato non ammesso: usa :values.',
    'mimetypes' => 'Formato non ammesso: usa :values.',
    'not_in' => 'Valore non valido.',
    'numeric' => 'Inserisci un numero.',
    'prohibited' => 'Questo campo non va compilato.',
    'regex' => 'Formato non valido.',
    'required_if' => 'Inserisci :attribute.',
    'required_unless' => 'Inserisci :attribute.',
    'required_with' => 'Inserisci :attribute.',
    'required_without' => 'Inserisci :attribute.',
    'size' => [
        'array' => 'Servono :size elementi.',
        'file' => 'Il file deve pesare :size KB.',
        'numeric' => 'Il valore deve essere :size.',
        'string' => 'Servono :size caratteri.',
    ],
    'starts_with' => 'Deve iniziare con: :values.',
    'uploaded' => 'Caricamento non riuscito: riprova.',
    'url' => 'Inserisci un indirizzo web valido.',
    'uuid' => 'Valore non valido.',

    // Testi su misura che il messaggio generico non può riprodurre.
    'custom' => [
        'birthDate' => [
            'before' => 'La data di nascita deve essere precedente a oggi.',
            'date_format' => 'Usa il formato gg/mm/aaaa.',
        ],
        'email' => [
            'unique' => 'Questa email è già registrata.',
        ],
        // Regalo Smartbox: il testo va in una mail verso un estraneo.
        'giftDedication.*' => [
            'max' => 'La dedica non può superare i 200 caratteri.',
        ],
        'giftMessage.*' => [
            'max' => 'Il messaggio non può superare i 500 caratteri.',
        ],
        // Modalità di pagamento del partner: è una scelta, "Inserisci" non si legge.
        'paymentMode' => [
            'required' => 'Scegli la modalità di pagamento.',
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
        'taxCode' => [
            'max' => 'Il codice fiscale non può superare i 16 caratteri.',
        ],
        'vat' => [
            'max' => 'La partita IVA non può superare i 13 caratteri.',
        ],
    ],

    // Nomi campo con articolo, così "Inserisci :attribute." resta naturale.
    'attributes' => [
        'accountHolder' => 'il titolare del conto',
        'address' => 'l\'indirizzo',
        'bic' => 'il BIC',
        'birthDate' => 'la data di nascita',
        'businessName' => 'la ragione sociale',
        'city' => 'la città',
        'description' => 'una descrizione',
        'email' => 'l\'email',
        'firstName' => 'il nome',
        'iban' => 'l\'IBAN',
        'lastName' => 'il cognome',
        'offerType' => 'il tipo di offerta',
        'password' => 'la password',
        'paymentUrl' => 'l\'indirizzo del sito',
        'petType' => 'la tipologia di animale',
        'phone' => 'il numero di cellulare',
        'postalCode' => 'il CAP',
        'province' => 'la provincia',
        'recipientEmail' => 'l\'email del destinatario',
        'role' => 'il tuo ruolo',
        'taxCode' => 'il codice fiscale',
        'vat' => 'la partita IVA',
        'website' => 'il sito web',
        'zip' => 'il CAP',
    ],
];
