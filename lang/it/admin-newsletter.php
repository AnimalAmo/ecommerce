<?php

/*
| Pannello di amministrazione, modulo Newsletter: solo italiano (il pannello
| sta fuori dalla localizzazione), per questo non è in LangParityTest.
| I testi che arrivano agli iscritti stanno in lang/{it,en}/newsletter.php.
*/

return [

    // Comandi artisan del modulo (newsletter:confirm-legacy, newsletter:resume).
    'command' => [
        'legacy_imported' => 'Contatti della vecchia casella aggiunti alla lista come da confermare: :count.',
        'legacy_pending' => 'Contatti della vecchia casella mai contattati: :count.',
        'legacy_dry_run' => 'Prova a vuoto: nessuna mail spedita. Rilancia senza --dry-run per mandare le conferme.',
        'legacy_sent' => 'Mail di conferma di cortesia messe in coda: :count. Chi non conferma non viene ricontattato.',
        'resume_not_sending' => 'Nessuna campagna in invio con questo id.',
        'resume_alive' => 'L\'invio sembra ancora in corso (ultima attività alle :time): riprenderlo raddoppierebbe il ritmo. Usa --force se sei sicuro che sia fermo.',
        'resume_done' => 'Invio ripreso: :count destinatari ancora da spedire.',
    ],

    // Errori mostrati nel pannello.
    'errors' => [
        'not_draft' => 'Questa newsletter è già partita: non si può inviare di nuovo.',
        'missing_italian' => 'Manca la versione italiana: oggetto e testo sono obbligatori.',
        'empty_audience' => 'Nessun iscritto confermato riceverebbe questa newsletter.',
    ],

];
