<?php

return [
    // Form di iscrizione nel piede del sito. `consent` è la frase accanto al
    // pulsante e viene salvata IDENTICA come prova del consenso: se la cambi,
    // cambia la prova delle iscrizioni da qui in avanti (quelle vecchie
    // conservano il testo che avevano letto).
    'footer' => [
        'heading' => 'Iscriviti alla newsletter',
        'intro' => 'Mete pet friendly, eventi e idee per viaggiare con il tuo animale. Una mail ogni tanto, niente spam.',
        'email_label' => 'Il tuo indirizzo email',
        'email_placeholder' => 'nome@esempio.it',
        'consent' => 'Iscrivendomi accetto di ricevere la newsletter di AnimalAmo. Posso disiscrivermi in qualsiasi momento dal link in fondo a ogni mail.',
        'privacy' => 'Informativa privacy',
        'submit' => 'Iscriviti',
        'success_title' => 'Controlla la tua casella',
        'success' => 'Se l\'indirizzo non è già iscritto, ti arriverà una mail: clicca il link per confermare l\'iscrizione.',
        'throttled' => 'Troppi tentativi. Riprova tra :minutes minuti.',
    ],

    'confirm' => [
        'page_title' => 'Conferma iscrizione',
        'title' => 'Confermi l\'iscrizione?',
        'text' => ':email riceverà la newsletter di AnimalAmo: mete pet friendly, eventi e idee per viaggiare con il tuo animale.',
        'submit' => 'Confermo l\'iscrizione',
        'done_title' => 'Iscrizione confermata',
        'done_text' => 'Grazie! Da ora riceverai la newsletter di AnimalAmo. Puoi disiscriverti quando vuoi dal link in fondo a ogni mail.',
        'invalid_title' => 'Link non valido',
        'invalid_text' => 'Il link di conferma non è valido o è scaduto. Puoi iscriverti di nuovo dal piede del sito: ti arriverà una mail nuova.',
        'back_home' => 'Torna alla home',
        // Prova del consenso di chi conferma senza aver compilato un form
        // (vecchia casella di registrazione): il clic sul link è il consenso.
        'consent' => 'Ho confermato dal link ricevuto via mail di voler ricevere la newsletter di AnimalAmo.',
    ],

    'unsubscribe' => [
        'page_title' => 'Disiscrizione dalla newsletter',
        'title' => 'Vuoi disiscriverti?',
        'text' => ':email non riceverà più la newsletter di AnimalAmo. Le mail sui tuoi ordini continueranno ad arrivare.',
        'submit' => 'Disiscrivimi',
        'done_title' => 'Disiscrizione completata',
        'done_text' => 'Non riceverai più la newsletter. Se cambi idea, puoi iscriverti di nuovo dal piede del sito.',
        'back_home' => 'Torna alla home',
    ],

    'mail' => [
        'confirm' => [
            'subject' => 'Conferma la tua iscrizione alla newsletter',
            'preheader' => 'Un clic e sei dentro: senza conferma non ti scriviamo.',
            'heading' => 'Manca solo un clic',
            'intro' => 'Hai chiesto di ricevere la newsletter di AnimalAmo. Per completare l\'iscrizione conferma il tuo indirizzo.',
            'courtesy_intro' => 'In passato, registrandoti su AnimalAmo, avevi spuntato la casella della newsletter. Stiamo rinnovando la lista e ti scriviamo una sola volta: se vuoi continuare a riceverla, conferma il tuo indirizzo.',
            'cta' => 'Confermo l\'iscrizione',
            'ignore' => 'Il link vale :days giorni. Se non sei stato tu, ignora questa mail: senza conferma non riceverai nulla.',
            'courtesy_ignore' => 'Il link vale :days giorni. Se non confermi non ti scriveremo più.',
        ],
        'layout' => [
            'reason' => 'Ricevi questa mail perché ti sei iscritto alla newsletter di AnimalAmo.',
            'unsubscribe' => 'Disiscriviti',
            'privacy' => 'Informativa privacy',
            'test_notice' => 'Invio di prova: il link per disiscriversi funziona solo negli invii reali.',
            'button_fallback' => 'Se il pulsante ":action" non funziona, copia e incolla questo indirizzo nel browser:',
        ],
    ],
];
