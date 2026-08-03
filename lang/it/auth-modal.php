<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modali login / registrazione / partner (XD "Pop-Up - Login")
    |--------------------------------------------------------------------------
    */

    // Comuni
    'close' => 'Chiudi',
    'welcome' => 'Benvenuto',
    'back' => 'Indietro',
    'email' => 'Email',
    'password' => 'Password',
    'forgot_password' => 'Password dimenticata',
    'login' => 'Accedi',

    // Card cliente (modale login)
    'client' => [
        'title' => 'Accedi come Cliente',
        'no_account' => 'Non hai un account?',
        'register_free' => 'Registrati gratuitamente',
    ],

    // Card / modale partner
    'partner' => [
        'title' => 'Accedi come Partner',
        'already_partner' => 'Sei già un partner?',
        'access_reserved_area' => 'Accedi alla tua area riservata',
        'want_to_become' => 'Vuoi diventare nostro partner?',
        'fill_form' => 'Compila il form',
        'not_yet_partner' => 'Non sei ancora partner?',
        'request_access' => 'Richiedi gli accessi',
    ],

    // Modale "Password dimenticata" (richiesta del link)
    'forgot' => [
        'title' => 'Password dimenticata',
        'intro' => 'Inserisci l’indirizzo email con cui ti sei registrato: ti invieremo il link per creare una nuova password.',
        'send_link' => 'Invia il link',
        'sent_title' => 'Controlla la tua email',
        'sent_text' => 'Se :email è associata a un account AnimalAmo, tra pochi minuti riceverai il link per reimpostare la password.',
        'sent_validity' => 'Il link resta valido per 60 minuti.',
        'sent_spam_hint' => 'Non trovi l’email? Controlla anche la cartella spam.',
        'back_to_login' => 'Torna al login',
    ],

    // Pagina di reimpostazione (link dell’email)
    'reset' => [
        'title' => 'Crea una nuova password',
        'intro' => 'Scegli una nuova password per l’account :email.',
        'new_password' => 'Nuova password',
        'repeat_password' => 'Ripeti la nuova password',
        'submit' => 'Salva la password',
        'done_title' => 'Password aggiornata',
        'done_text' => 'Da ora puoi accedere ad AnimalAmo con la tua nuova password.',
        'done_cta' => 'Accedi',
        'invalid_title' => 'Link non più valido',
        'invalid_text' => 'Il link per reimpostare la password è scaduto o è già stato usato. Richiedine uno nuovo: bastano pochi secondi.',
        'invalid_cta' => 'Richiedi un nuovo link',
        'back_home' => 'Torna alla home',
        'title_page' => 'Reimposta la password',
    ],

    // Email con il link di reimpostazione (nessun design XD: markdown Laravel)
    'reset_mail' => [
        'subject' => 'Reimposta la tua password AnimalAmo',
        'heading' => 'Ciao :name,',
        'intro' => 'Abbiamo ricevuto una richiesta di reimpostazione della password per l’account AnimalAmo collegato a questo indirizzo email.',
        'cta' => 'Reimposta la password',
        'expiry' => 'Il link è valido per :count minuti.',
        'ignore' => 'Se non hai richiesto tu il cambio password puoi ignorare questa email: la tua password resta invariata.',
        'signature' => 'A presto,',
    ],

    // Modale registrazione a step
    'register' => [
        'personal_info' => 'Informazioni personali',
        'address' => 'Indirizzo',
        'pet' => 'Animale domestico',
        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'birth_date' => 'Data di nascita',
        'phone' => 'Cellulare',
        'repeat_password' => 'Ripeti password',
        'address_field' => 'Indirizzo',
        'city' => 'Città',
        'postal_code' => 'Cap',
        'pet_type' => 'Tipologia animale',
        'newsletter' => 'Iscriviti alla newsletter',
        'privacy_consent' => 'Acconsento all’uso dei miei dati personali per ricevere promozioni esclusive.',
        'continue' => 'Prosegui',
        'create_profile' => 'Crea profilo',
    ],

];
